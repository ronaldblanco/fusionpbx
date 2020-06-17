<?php

if (!class_exists('crm_call_1')) {
	class crm_call_1 {

        public $is_ready;

        public function __construct($session = False) {

            $this->is_ready = True;
        }

        # Just a failsafe not to throw an error
        public function __call($name, $arguments) {
            return;
        }


        public function process(&$xml_varibles) {

            $url =  strlen($xml_varibles->crm_end_settings_url) > 0 ? base64_decode(urldecode($xml_varibles->crm_end_settings_url), true) : False;

            if (!$url or strlen($url) == 0) {
                return;
            }

            $send_data = array(
                'url' => $url,
                'fields' => array(
                    'timestamp' => strval($xml_varibles->end_epoch),
                    'direction' => strval($xml_varibles->direction),
                    'ivr_path' => rtrim(strval($xml_varibles->ivr_path),'_'),
                    'uuid' => strval($xml_varibles->uuid),
                )
            );
            // Get correct hangup

            $hangup_cause = strlen(strval($xml_varibles->originate_disposition)) > 0 ? strval($xml_varibles->originate_disposition) : strval($xml_varibles->hangup_cause);

            switch ($hangup_cause) {
                case 'SUCCESS':
                case 'NORMAL_CLEARING':
                    $send_data['fields']['status'] = 'answered';
                    break;
                case 'CALL_REJECTED':
                case 'SUBSCRIBER_ABSENT':
                case 'USER_BUSY':
                    $send_data['fields']['status'] = 'busy';
                    break;
                case 'NO_ANSWER':
                case 'ALLOTTED_TIMEOUT':
                case 'NO_USER_RESPONSE':
                case 'ORIGINATOR_CANCEL':
                case 'LOSE_RACE': // This cause usually in ring groups, so this call is not ended.
                    $send_data['fields']['status'] = 'no answer';
                    break;
                default:
                    $send_data['fields']['status'] = 'failed';
            }

            $send_data['fields']['src'] = array(
                'name' => strval($xml_varibles->caller_id_name),
                'number' => strval($xml_varibles->caller_id_number),
            );

            $crm_name = strlen(strval($xml_varibles->crm_first_name)) > 0 ? strval($xml_varibles->crm_first_name) : "";
            $crm_name .= " " . strlen(strval($xml_varibles->crm_last_name)) > 0 ? strval($xml_varibles->crm_last_name) : "";

            if (strlen($crm_name) == 1) {
                $crm_name = strlen(strval($xml_varibles->last_sent_callee_id_name)) > 0 ? strval($xml_varibles->last_sent_callee_id_name) : strval($xml_varibles->caller_destination);
            }

            $send_data['fields']['last_seen'] = array(
                'name' => $crm_name,
                'number' => strlen(strval($xml_varibles->last_sent_callee_id_number)) > 0 ? strval($xml_varibles->last_sent_callee_id_number) : strval($xml_varibles->caller_destination)
            );

            $send_data['fields']['time'] = array(
                'duration' => strval($xml_varibles->duration),
                'answered' => strval($xml_varibles->billsec),
            );

            if (strlen(strval($xml_varibles->record_name)) > 0) {
                $record_link = strval($xml_varibles->record_path);
                $record_link = explode('archive', $record_link)[1];
                if (strlen($record_link) > 0) {
                    $send_data['fields']['recording'] =  $record_link . "/" . strval($xml_varibles->record_name);
                }
            }

            $this->send($url, $send_data);
        }

        private function send($url, $data, $is_json = True) {

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

            if ($is_json) {

                $data_string = json_encode($data['fields']);

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',
                                                        'Content-Length: ' . strlen($data_string)
                                                    ));
            } else {
                $data_string = http_build_query($data['fields']);
                curl_setopt($ch,CURLOPT_POST, 1);
                curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
            }

            $resp = curl_exec($ch);
            $err = curl_error($ch);

            curl_close($ch);

            $log_string = ($err) ? "[ERROR] " . $err : "[SUCCESS] ";
            $log_string .= " -> ".$data['url']. " Req:".$data_string." Resp:".$resp."\n";

            file_put_contents('/tmp/api_crm_call_1.log', $log_string);

        }
    }
}


?>