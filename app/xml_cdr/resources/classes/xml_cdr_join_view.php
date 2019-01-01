<?php
if (!class_exists('xml_cdr_join_view')) {

	class xml_cdr_join_view {

        private $enabled = false;
        private $is_uuid;
        private $is_close_match;
        private $is_loose_race;

        public function __construct($session_options) {
            if (!$session_options) {
                $this->enabled = false;
                return;
            }

            if ($session_options['enabled'] != 'true') {
                $this->enabled = false;
                return;
            }

            $this->enabled = true;

            $options = $session_options['value'];
            
            $this->is_uuid = (strpos($options, "uuid") != false);
            $this->is_close_match = (strpos($options, "close_match") != false);
            $this->is_loose_race = (strpos($options, "loose_race") != false);
        }

        private function uuid_cleanup(&$xml_cdr_data) {

        }

        private function loose_race_cleanup(&$xml_cdr_data) {

        }

        private function close_match_cleanup(&$xml_cdr_data) {

        }


        public function cleanup(&$xml_cdr_data) {
            if (!$this->enabled) {
                return;
            }

            if ($this->is_uuid) {
                $this->uuid_cleanup($xml_cdr_data);
            }

            if ($this->is_close_match) {
                $this->close_match_cleanup($xml_cdr_data);
            }

            if ($this->is_loose_race) {
                $this->loose_race_cleanup()($xml_cdr_data);
            }
        }
    }
}
?>