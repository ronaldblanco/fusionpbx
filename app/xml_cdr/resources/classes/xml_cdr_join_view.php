<?php

// Adding 'hidden' or 'joined' attribute to xml array


if (!class_exists('xml_cdr_join_view')) {

    class xml_cdr_join_view {

        private $enabled;
        private $is_uuid;
        private $is_close_match;
        private $is_loose_race;

        public function __construct($session_options) {

            if (!$session_options || !isset($session_options['join_view'])) {
                $this->enabled = false;
                return;
            }

            if ($session_options['join_view']['uuid'] == '') {
                $this->enabled = false;
                return;
            }

            $this->enabled = true;

            $options = $session_options['join_view']['text'];
            
            $this->is_uuid = (strpos($options, "uuid") != false);
            $this->is_close_match = (strpos($options, "close_match") != false);
            $this->is_lose_race = (strpos($options, "lose_race") != false);

            return;
        }

        private function uuid_cleanup(&$xml_cdr_data) {
            // Yes, here we have 0^2 complexity. I know, but it's really quick'n'dirty way of doing this
            // Plus we're using it not more than on ~50-100 array size, so not super big data.
            foreach ($xml_cdr_data as $xml_cdr_data_key => $xml_cdr_data_line) {

                // Not process already hidden data. Small optimization
                if (isset($xml_cdr_data_line['hidden'])) {
                    continue;
                }

                if (isset($xml_cdr_data_line['uuids']) && strlen($xml_cdr_data_line['uuids']) > 0) {
                    $uuids_to_hide = $xml_cdr_data_line['uuids'];
                    foreach ($xml_cdr_data as $k => $v) {
                        if (strpos($uuids_to_hide, $v['uuid']) != false) {
                            $xml_cdr_data[$k]['hidden'] = true;
                            // Yes, could be multiple assignments, but here it's done to be sure
                            $xml_cdr_data[$xml_cdr_data_key]['joined'] = true;
                        }
                    }
                }
            }
        }

        private function lose_race_cleanup(&$xml_cdr_data) {
            foreach ($xml_cdr_data as $xml_cdr_data_key => $xml_cdr_data_line) {

                if (isset($xml_cdr_data_line['hidden'])) {
                    continue;
                }

                if ($xml_cdr_data_line['hangup_cause'] == 'LOSE_RACE') {
                    $xml_cdr_data[$xml_cdr_data_key]['hidden'] = true;
                }
            }
        }

        private function close_match_cleanup(&$xml_cdr_data) {
            // Nothing here yet. TBD
        }


        public function status() {
            return $this->enabled;
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

            if ($this->is_lose_race) {
                $this->lose_race_cleanup($xml_cdr_data);
            }
        }
    }
}
?>