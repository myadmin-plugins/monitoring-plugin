<?php
/**
 * monitoring_setup()
 *
 * @return bool
 * @throws \Exception
 * @throws \SmartyException
 */
    function monitoring_setup()
    {
        return false;
        page_title('Monitoring Setup');
        $db = clone $GLOBALS['tf']->db;
        $db2 = clone $db;
        if ($GLOBALS['tf']->ima == 'admin') {
            $custid = (int)$GLOBALS['tf']->variables->request['custid'];
        } else {
            $custid = $GLOBALS['tf']->session->account_id;
        }
        $data = $GLOBALS['tf']->accounts->read($custid);
        $id = (int)$GLOBALS['tf']->variables->request['id'];
        if ($GLOBALS['tf']->ima == 'admin') {
            $db->query("select * from monitoring where monitoring_id='{$id}'");
        } else {
            $db->query("select * from monitoring where monitoring_id='{$id}' and monitoring_custid='{$custid}'");
        }
        if ($db->num_rows() == 0) {
            add_output('Invalid Monitoring Item');
            return false;
        }
        function_requirements('get_monitoring_services');
        $services = get_monitoring_services();
        $db->next_record(MYSQL_ASSOC);
        $extra = parse_monitoring_extra($db->Record['monitoring_extra']);
        if (isset($GLOBALS['tf']->variables->request['hostname']) && verify_csrf('monitoring_setup')) {
            $hostname = $db->real_escape($GLOBALS['tf']->variables->request['hostname']);
            $ip = $db->real_escape($GLOBALS['tf']->variables->request['ip']);
            $comment = $db->real_escape($GLOBALS['tf']->variables->request['comment']);
            $extra['email'] = $GLOBALS['tf']->variables->request['email'];
            foreach ($services as $service) {
                $extra[$service] = $GLOBALS['tf']->variables->request[$service];
            }
            $extra_string = $db->real_escape(myadmin_stringify($extra));
            $db->query("update monitoring set monitoring_hostname='{$hostname}', monitoring_ip='{$ip}', monitoring_comment='{$comment}', monitoring_extra='{$extra_string}' where monitoring_id=$id");
            $db->query("select * from monitoring where monitoring_id=$id");
            $db->next_record(MYSQL_ASSOC);
            add_output('Monitoring Selection Updated');
            $GLOBALS['tf']->redirect($GLOBALS['tf']->link('index.php', 'choice=none.monitoring'));
        }
        $table = new TFTable();
        $table->add_hidden('id', $id);
        $table->csrf('monitoring_setup');
        $table->set_title('Server Monitoring');
        $table->add_field('Hostname', 'r');
        $table->set_colspan(2);
        $table->add_field($table->make_input('hostname', htmlspecial($db->Record['monitoring_hostname']), 20));
        $table->add_row();
        $table->add_field('IP', 'r');
        $table->set_colspan(2);
        $table->add_field($table->make_input('ip', htmlspecial($db->Record['monitoring_ip']), 20));
        $table->add_row();
        $table->add_field('Comment', 'r');
        $table->set_colspan(2);
        $table->add_field($table->make_input('comment', htmlspecial($db->Record['monitoring_comment']), 20));
        $table->add_row();
        $table->add_field('Email To', 'r');
        $table->set_colspan(2);
        $table->add_field($table->make_input('email', (isset($extra['email']) ? htmlspecial($extra['email']) : htmlspecial($data['account_lid'])), 20));
        $table->add_row();
        $table->add_field('Service');
        $table->add_field('Monitor');
        $table->add_field('Status');
        $table->add_row();
        $services = get_monitoring_services();
        foreach ($services as $service) {
            $db2->query(
                "select * from monitoring_history where history_type='" . $db->real_escape($db->Record['monitoring_ip']) . "' and history_section='monitoring_$service' order by history_id desc limit 1",
                __LINE__,
                __FILE__
            );
            $table->add_field($service, 'r');
            //$table->add_field('<select name="'.$service.'"><option value=0>No</option><option '.(isset($extra[$service]) && $extra[$service] == '1' ? 'selected' : '').' value=1>Yes</option></select>');
            $table->add_field('<input type="radio" name="'.$service.'" value="0" '.(!isset($extra[$service]) || $extra[$service] != '1' ? 'checked' : '').'>No <input type="radio" name="'.$service .
                '" value="1" '.(isset($extra[$service]) && $extra[$service] == '1' ? 'checked' : '').'>Yes');
            if ($db2->num_rows() > 0) {
                $db2->next_record(MYSQL_ASSOC);
                $status = ($db2->Record['history_new_value'] == 1 ? 'Up' : 'Down');
            } else {
                $status = '-';
            }
            $table->add_field($status);
            $table->add_row();
        }
        $table->set_colspan(3);
        $table->add_field($table->make_submit('Update'));
        $table->add_row();
        add_output($table->get_table());
        return true;
    }
