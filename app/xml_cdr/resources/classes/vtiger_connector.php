<?php


/* 		// Call VTiger API
        $vtiger_crm_connector_enable = isset($_SESSION['vtiger_connector']['enable']['boolean']) ? filter_var($_SESSION['vtiger_connector']['enable']['boolean'], FILTER_VALIDATE_BOOLEAN) : False;
        if ($vtiger_crm_connector_enable && strlen($start_stamp) > 0) {
            $vtiger_url = strlen($xml->variables->vtiger_url) > 0 ? base64_decode(urldecode($xml->variables->vtiger_url), true) : False;
            $vtiger_api_key = strlen($xml->variables->vtiger_api_key) > 0 ? base64_decode(urldecode($xml->variables->vtiger_api_key), true) : False;

            $vtiger_record_path = False;

            if (isset($database->fields['recording_file']) and strlen($xml->variables->vtiger_record_path) > 0) { 
                $vtiger_record_path = base64_decode(urldecode($xml->variables->vtiger_record_path)).$recording_relative_path.'/'.$uuid.$recording_extension;
            }

            $vtiger_api_call = new vtiger_connector($vtiger_url, $vtiger_api_key, $database->fields, $vtiger_record_path);
            if ($vtiger_api_call) {
                $vtiger_api_call->send();
            }
            unset($vtiger_url);
            unset($vtiger_api_key);
            unset($vtiger_record_path);
            unset($vtiger_api_call);
        } */

if (!class_exists('vtiger_connector')) {
	class vtiger_connector {

        public $is_ready;

        public function __construct($session = False) {

            $this->is_ready = True;
        }

        # Just a failsafe not to throw an error
        public function __call($name, $arguments) {
            return;
        }


        public function process(&$xml_varibles) {

            $url =  strlen($xml_varibles->vtiger_url) > 0 ? base64_decode(urldecode($xml_varibles->vtiger_url), true) : False;
            $key = strlen($xml_varibles->vtiger_api_key) > 0 ? base64_decode(urldecode($xml_varibles->vtiger_api_key), true) : False;

            if (!$url or !$key) {
                return;
            }

            $send_data = array(
                'url' => $url,
                'key' => $key,
                'uuid' => $xml_varibles->uuid,
                'fields' => array(
                    'timestamp' => $xml_varibles->end_epoch,
                    'direction' => $xml_varibles->direction,
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

            $time = array();
            $time['duration'] = $database_fields['duration'];
            $time['answered'] = $database_fields['billsec'];
            $send_data['fields']['time'] = array(
                'duration' => $xml_varibles->duration,
                'answered' => $xml_varibles->billsec,
            );

            if (strlen(strval($xml_varibles->vtiger_record_path)) > 0) {
                $send_data['fields']['recording'] = base64_decode(urldecode($xml_varibles->vtiger_record_path));
            }

            $this->send($send_data);
        }

        private function send($data) {

            
            $data_string = json_encode($data['fields']);

            $ch = curl_init($this->url.'/call_end');
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
            curl_close($ch);

            file_put_contents('/tmp/api_vtiger.log', " -> ".$data['url'].'/call_end'. " Req:".$data_string." Resp:".$resp."\n");

        }
    }
}


?>