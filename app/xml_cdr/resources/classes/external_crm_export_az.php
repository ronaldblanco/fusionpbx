<?php
if (!class_exists('external_crm_export_az')) {
    class external_crm_export {
        public $is_ready;

        private $crm_url;

        public function __construct($session) {

            $crm_url = isset($this->session['crm_export_az']['text']) ? $this->session['crm_export_az']['text'] : False;

            $this->is_ready = ($crm_url == True) ? True : False;

        }
        # Just a failsafe not to throw an error
        public function __call($name, $arguments) {
            return;
        }

        private function send_request() {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->crm_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "", 
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: text/xml",
                ),
            ));
        
            $response = curl_exec($curl);
            $err = curl_error($curl);
        
            curl_close($curl);
        
            if ($err) {
                return False;
            }
            return $response;
        }

        public function process($database_fields) {
            

        }
    }
}

?>