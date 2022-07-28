<?php

    /**
     * Monitoring Related Functionality
     * @author Joe Huss <detain@interserver.net>
     * @copyright 2019
     * @package MyAdmin
     * @category Monitoring
     */

    function website_scan()
    {
        $table = new TFTable();
        $table->set_title("Scan website for possible virus's");
        $table->add_field('Website To Scan');
        $table->add_field($table->make_input('website', (isset($GLOBALS['tf']->variables->request['website']) ? $GLOBALS['tf']->variables->request['website'] : ''), 40));
        $table->add_field($table->make_submit('Scan'));
        $table->add_row();
        add_output($table->get_table());
        if (isset($GLOBALS['tf']->variables->request['website'])) {
            $table = new TFTable();
            $table->set_title('Scan result for '.$GLOBALS['tf']->variables->request['website']);
            $serialized_data = getcurlpage('http://sitecheck.sucuri.net/scanner/?scan='.urlencode($GLOBALS['tf']->variables->request['website']).'&serialized&interserver.net');
            //print_r($serialized_data);
            $data = myadmin_unstringify($serialized_data);
            //echo '<pre>';print_r($data);echo '</pre>';
            $last_key = '';
            foreach ($data as $key => $sub_data) {
                if ($last_key != $key) {
                    $table->set_colspan(2);
                    $table->add_field($key, 'l');
                    $table->add_row();
                }
                $last_key = $key;
                if ($key != 'BLACKLIST') {
                    foreach ($sub_data as $sub_key => $values) {
                        foreach ($values as $value) {
                            $table->add_field($sub_key, 'r');
                            $table->add_field($value, 'r');
                            $table->add_row();
                        }
                    }
                } else {
                    foreach ($sub_data as $sub_key => $values) {
                        foreach ($values as $value) {
                            $table->add_field($value[0], 'r');
                            $table->add_field($value[1], 'r');
                            $table->add_row();
                        }
                    }
                }
            }
            add_output($table->get_table());
        }
    }
