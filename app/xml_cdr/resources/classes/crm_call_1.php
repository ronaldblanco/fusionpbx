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
                    'ivr_path' => strval($xml_varibles->ivr_path),
                    'uuid' => strval($xml_varibles->uuid),
                )
            );
            // Get correct hangup
            switch ($xml_varibles->hangup_cause) {
                case 'NORMAL_CLEARING':
                    $send_data['fields']['status'] = 'answered';
                    break;
                case 'CALL_REJECTED':
                case 'SUBSCRIBER_ABSENT':
                case 'USER_BUSY':
                    $send_data['fields']['status'] = 'busy';
                    break;
                case 'NO_ANSWER':
                case 'NO_USER_RESPONSE':
                case 'ORIGINATOR_CANCEL':
                case 'LOSE_RACE': // This cause usually in ring groups, so this call is not ended.
                    $send_data['fields']['status'] = 'no answer';
                    break;
                default:
                    $send_data['fields']['status'] = 'failed';
            }

            $send_data['fields']['src'] = array(
                'name' => $xml_varibles->caller_id_name,
                'number' => $xml_varibles->caller_id_number,
            );

            $send_data['fields']['last_seen'] = array(
                'name' => strlen(strval($xml_varibles->last_sent_callee_id_name)) > 0 ? strval($xml_varibles->last_sent_callee_id_name) : strval($xml_varibles->caller_destination),
                'number' => strlen(strval($xml_varibles->last_sent_callee_id_number)) > 0 ? strval($xml_varibles->last_sent_callee_id_number) : strval($xml_varibles->caller_destination)
            );

            $send_data['fields']['time'] = array(
                'duration' => strval($xml_varibles->duration),
                'answered' => strval($xml_varibles->billsec),
            );

            if (strlen(strval($xml_varibles->vtiger_record_path)) > 0) {
                $send_data['fields']['recording'] = base64_decode(urldecode($xml_varibles->vtiger_record_path));
            }

            $this->send($send_data);
        }

        private function send($data) {

            
            $data_string = json_encode($data['fields']);

            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',
                                                    'Content-Length: ' . strlen($data_string)
                                                ));

            $resp = curl_exec($ch);
            $err = curl_error($curl);

            curl_close($ch);

            $log_string = ($err) ? "[ERROR] " . $err : "[SUCCESS] ";
            $log_string .= " -> ".$data['url']. " Req:".$data_string." Resp:".$resp."\n";

            file_put_contents('/tmp/api_crm_call_1.log', $log_string);

        }
    }
}


?>