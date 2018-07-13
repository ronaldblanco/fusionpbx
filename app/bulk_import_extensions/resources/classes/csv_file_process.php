<?php
if (!class_exists('csv_file_process')) {
    class csv_file_process {
        
        private $csv_file;
        private $combination_keys;
        
        public function __construct($file_path) {
            if (!file_exists($file_path)) {
                $this->csv_file = False;
                return;
            }
            $this->csv_file = new SplFileObject($file_path);
            
            // Guessing CSV delimiter

            if (count($this->csv_file->fgetcsv()) != 1) {
                return;
            }

            // Trying ';'
            $this->csv_file->rewind();
            $this->csv_file->setCsvControl(";");
            if (count($this->csv_file->fgetcsv()) != 1) {
                return;
            }

            // Trying 'tab'
            $this->csv_file->rewind();
            $this->csv_file->setCsvControl("\t");
            if (count($this->csv_file->fgetcsv()) != 1) {
                return;
            }

            // Trying 'space'
            $this->csv_file->rewind();
            $this->csv_file->setCsvControl(" ");
            if (count($this->csv_file->fgetcsv()) != 1) {
                return;
            }
            // Trying ':'
            $this->csv_file->rewind();
            $this->csv_file->setCsvControl(":");
            if (count($this->csv_file->fgetcsv()) != 1) {
                return;
            }
            // Cannot get csv file delimiter. Unsetting file
            unset($this->csv_file);
        }

        public function __destruct() {
            unset($this->csv_file);
        }

        public function is_valid() {
            if ($this->csv_file) {
                return True;
            }
            return False;
        }

        public function read_first($number_to_read = 4) {

            $this->csv_file->rewind();
            
            if (!$this->csv_file->valid()) {
                return False;
            }
            $result = array();
            for ($i = 1; $i < $number_to_read; $i++) {
                if (!$this->csv_file->valid()) {
                    break;
                }
                $result[] = $this->csv_file->fgetcsv();
            }
            $this->csv_file->rewind();
            return $result;
        }
    }
}
?>