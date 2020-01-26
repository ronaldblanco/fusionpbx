<?php
class school_bell_selector {

    private $min, $hou, $day;

    function __construct() {

        # Fill min
        $this->min = array();
        for ($i = 0; $i <= 59; $i++) {
            $this->min[] = sprintf("%1$02d", $i);
        }
        
        # Fill hou
        $this->hou = array();
        for ($i = 0; $i <= 23; $i++) {
            $this->hou[] = sprintf("%1$02d", $i);
        }

        $this->day = array();
        for ($i = 1; $i <= 31; $i++) {
            $this->day[] = sprintf("%1$02d", $i);
        }

        # Fill dow
        $this->dow = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        # Fill month
        $this->mon = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        # Fill yea
        $current_year = date("Y");
        $this->yea = [$current_year - 1, $current_year, $current_year + 1, $current_year + 2];

    }

    private function _option_string($option, $selected) {
        if ($option == $selected) {
            return "<option selected value='" . $option . "'>" . $option . "</option>\n";
        }
        return "<option value='" . $option . "'>" . $option . "</option>\n";
    }

    private function _draw_selected($range_array, $selected, $draw_asterisk = False) {
        $selector_text = "<select name = 'aaa' id = 'bbb' class='formfld'>\n";

        if ($draw_asterisk) {
            array_unshift($range_array, "*");
        }

        foreach ($range_array as $draw_item) {
            $selector_text .= $this->_option_string($draw_item, $selected);
        }
        $selector_text .= "</select>";
        return $selector_text;
    }

    public function draw_min($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->min, $selected, $draw_asterisk);
    }

    public function draw_hou($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->hou, $selected, $draw_asterisk);
    }

    public function draw_day($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->day, $selected, $draw_asterisk);
    }

    public function draw_mon($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->mon, $selected, $draw_asterisk);
    }

    public function draw_dow($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->dow, $selected, $draw_asterisk);
    }

    public function draw_yea($selected = '', $draw_asterisk = False) {
        return $this->_draw_selected($this->yea, $selected, $draw_asterisk);
    }
}
?>