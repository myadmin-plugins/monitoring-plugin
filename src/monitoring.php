<?php
/**
 * monitoring()
 *
 * @return void
 * @throws \Exception
 * @throws \SmartyException
 */
	function monitoring() {
		page_title('Server And Software Monitoring And Notifications Section');
		$db = clone $GLOBALS['tf']->db;
		$db2 = clone $db;
		if ($GLOBALS['tf']->ima == 'admin') {
			if (isset($GLOBALS['tf']->variables->request['custid'])) {
				$custid = $db->real_escape($GLOBALS['tf']->variables->request['custid']);
			} else {
				$custid = $GLOBALS['tf']->session->account_id;
			}
		} else {
			$custid = $GLOBALS['tf']->session->account_id;
		}
		$data = $GLOBALS['tf']->accounts->read($custid);
		$notifications = ['once' => 'Only let me know once when a server is down until it is back up.', 'every' => 'Keep letting me know the server is down until its back up'];
		if (isset($GLOBALS['tf']->variables->request['notification']) && verify_csrf('monitoring')) {
			$notification = $db->real_escape($GLOBALS['tf']->variables->request['notification']);
			$GLOBALS['tf']->accounts->update($custid, ['notification' => $notification]);
			$data = $GLOBALS['tf']->accounts->read($custid);
			dialog('Setting Updated', 'You have updated your Monitoring Notification settings to: '.$notifications[$notification]);
		}
		$table = new TFTable;
		$table->csrf('monitoring');
		$table->set_title('Monitoring Options');
		$table->add_field('Downed Notification');
		$table->add_field(build_select('notification', $notifications, (isset($data['notification']) ? $data['notification'] : '')));
		$table->add_row();
		$table->set_colspan(2);
		$table->add_field($table->make_submit('Update Monitoring Options'));
		$table->add_row();
		add_output($table->get_table().'<br>');
		if (isset($GLOBALS['tf']->variables->request['newip'])) {
			if (validIp($GLOBALS['tf']->variables->request['newip'])) {
				$hostname = strip_tags($GLOBALS['tf']->variables->request['newhostname']);
				$ip = $GLOBALS['tf']->variables->request['newip'];
				$comment = strip_tags($GLOBALS['tf']->variables->request['newcomment']);
				$extra = [];
				$extra = $db->real_escape(myadmin_stringify($extra));
				$db->query(make_insert_query('monitoring', [
					'monitoring_id' => null,
					'monitoring_hostname' => $hostname,
					'monitoring_ip' => $ip,
					'monitoring_custid' => $custid,
					'monitoring_comment' => $comment,
					'monitoring_extra' => $extra
														 ]
						   ), __LINE__, __FILE__);
				$id = $db->getLastInsertId('monitoring', 'monitoring_id');
				$GLOBALS['tf']->redirect($GLOBALS['tf']->link('index.php', 'choice=none.monitoring_setup&amp;id='.$id));
			} else {
				dialog('Invalid IP', 'Invalid IP Address');
			}
		}
		if ($GLOBALS['tf']->ima == 'admin') {
			if (isset($GLOBALS['tf']->variables->request['delete'])) {
				$db->query("delete from monitoring where monitoring_id='" . (int)$GLOBALS['tf']->variables->request['id']
						   . "'", __LINE__, __FILE__);
			}
		} else {
			if (isset($GLOBALS['tf']->variables->request['delete'])) {
				$db->query("delete from monitoring where monitoring_custid='{$custid}' and monitoring_id='" . (int)$GLOBALS['tf']->variables->request['id']
						   . "'", __LINE__, __FILE__);
			}
		}
		add_output(render_form('monitoring_list').'<br>');
		function_requirements('get_umonitored_server_table');
		add_output(get_umonitored_server_table());
	}
