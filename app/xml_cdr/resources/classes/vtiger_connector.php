<?php

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

            $direction = strlen(strval($xml_varibles->call_direction)) > 0 ? strval($xml_varibles->call_direction) : strval($xml_varibles->direction);
            $uuid = strlen(strval($xml_varibles->vtiger_call_uuid)) > 0 ? strval($xml_varibles->vtiger_call_uuid) : strval($xml_varibles->call_uuid);

            $send_data = array(
                'url' => $url,
                'fields' => array(
                    'timestamp' => strval($xml_varibles->end_epoch),
                    'direction' => $direction,
                    'vtigersignature' => $key,
                    'uuid' => $uuid,
                    'callstatus' => 'call_end',
                )
            );

            $dialstatus = strval($xml_varibles->DIALSTATUS);
            $sip_hangup_disposition = strval($xml_varibles->sip_hangup_disposition);

            // Get correct hangup
            switch ($dialstatus) {
                case 'CANCEL':
                case 'BUSY':
                    $send_data['fields']['status'] = 'busy';
                    break;
                case 'NOANSWER':
                    $send_data['fields']['status'] = 'no answer';
                    break;
                case 'SUCCESS':
                    $send_data['fields']['status'] = 'answered';
                    if ($sip_hangup_disposition == "recv_cancel") {
                        $send_data['fields']['status'] = 'busy';
                    }
                    break;
                default:
                    // No Dialstatus
                    switch ($sip_hangup_disposition) {
                        case "send_cancel":
                            $send_data['fields']['status'] = 'no answer';
                            break;
                        case "recv_bye":
                        case "send_bye":
                            $send_data['fields']['status'] = 'answered';
                            break;
                        case "send_refuse":
                            $send_data['fields']['status'] = 'busy';
                            break;
                    }
            }
            if (!$send_data['fields']['status']) {
                $send_data['fields']['status'] = 'failed';
            }

            $send_data['fields']['src'] = array(
                'name' => strval($xml_varibles->caller_id_name),
                'number' => strval($xml_varibles->caller_id_number),
            );

            $send_data['fields']['last_seen'] = array(
                'name' => strlen(strval($xml_varibles->last_sent_callee_id_name)) > 0 ? strval($xml_varibles->last_sent_callee_id_name) : strval($xml_varibles->caller_destination),
                'number' => strlen(strval($xml_varibles->last_sent_callee_id_number)) > 0 ? strval($xml_varibles->last_sent_callee_id_number) : strval($xml_varibles->caller_destination)
            );

            $send_data['fields']['time'] = array(
                'duration' => strval($xml_varibles->duration),
                'answered' => strval($xml_varibles->billsec),
            );

            if (strlen(strval($xml_varibles->record_name)) > 0 && strlen(strval($xml_varibles->vtiger_record_path)) > 0) {
                $record_link = strval($xml_varibles->record_path);
                $record_link = explode('recordings', $record_link)[1];
                
                if (strlen($record_link) > 0) {
                    $vtiger_record_path = base64_decode(urldecode($xml_varibles->vtiger_record_path));
                    if (substr($vtiger_record_path, -1) == "/") {
                        $vtiger_record_path  = substr($vtiger_record_path, 0, -1);
                    }
                    $record_link = $vtiger_record_path . $record_link;
                    $send_data['fields']['recording'] =  $record_link . "/" . strval($xml_varibles->record_name);
                }
            }

            $this->send($send_data);
        }

        private function send($data) {

            
            $data_string = json_encode($data['fields']);

            $ch = curl_init($data['url']);
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
            $err = curl_error($ch);
            curl_close($ch);

            $resp_text = " Responce: " . $resp;
            if ($err) {
                $resp_text = "Error: " . $err;
            }
            file_put_contents('/tmp/api_vtiger.log', " -> " . $data['url'] .  " Req:" . $data_string . $resp_text . "\n");

        }
    }
}


?>