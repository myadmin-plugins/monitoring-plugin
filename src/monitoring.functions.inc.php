<?php
	/**
	 * Monitoring Related Functionality
	 * @author Joe Huss <detain@interserver.net>
	 * @copyright 2019
	 * @package MyAdmin
	 * @category Monitoring
	 */

	/**
	 * parse_monitoring_extra()
	 * @param mixed $extra
	 * @return array|mixed
	 */
	function parse_monitoring_extra($extra)
	{
		if ($extra == '') {
			return [];
		}
		$ret = myadmin_unstringify($extra);
		if (!is_array($ret)) {
			$ret = [];
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	function get_monitoring_services()
	{
		$services = [
			'ping',
			'http',
			'smtp',
			'ftp',
			'dns',
			'imap',
			'pop',
			'ssh'
		];
		return $services;
	}

	/**
	 * @return array
	 */
	function get_monitoring_data()
	{
		$services = get_monitoring_services();
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
		if ($GLOBALS['tf']->ima == 'admin') {
			$data_query = "
SELECT
  substr(monitoring_history.history_section, 12) AS service
, monitoring_history.history_type AS ip
, monitoring_history.history_timestamp AS last_time
, monitoring_history.history_new_value AS status
  FROM
	(
	SELECT
	  monitoring_history.history_section AS service
	, monitoring_history.history_type AS ip
	, max(monitoring_history.history_timestamp) AS last_event
	  FROM
		(
		SELECT
		  monitoring.monitoring_ip
		  FROM
			monitoring
		  GROUP BY
			monitoring.monitoring_ip) monitored_ips
	  LEFT OUTER JOIN monitoring_history
	  ON monitored_ips.monitoring_ip = monitoring_history.history_type
	  WHERE
		monitoring_history.history_section LIKE 'monitoring_%'
	  GROUP BY
		monitoring_history.history_type
	  , monitoring_history.history_section) last_events_list
  LEFT OUTER JOIN monitoring_history
  ON last_events_list.service = monitoring_history.history_section AND last_events_list.ip = monitoring_history.history_type AND last_events_list.last_event = monitoring_history.history_timestamp
  GROUP BY
	monitoring_history.history_type
  , monitoring_history.history_section
  , monitoring_history.history_timestamp
  , monitoring_history.history_new_value
";
			$monitor_query = 'select * from monitoring';
		} else {
			$data_query = "
SELECT
  substr(monitoring_history.history_section, 12) AS service
, monitoring_history.history_type AS ip
, monitoring_history.history_timestamp AS last_time
, monitoring_history.history_new_value AS status
  FROM
	(
	SELECT
	  monitoring_history.history_section AS service
	, monitoring_history.history_type AS ip
	, max(monitoring_history.history_timestamp) AS last_event
	  FROM
		(
		SELECT
		  monitoring.monitoring_ip
		  FROM
			monitoring
		  WHERE
			monitoring.monitoring_custid='{$custid}'
		  GROUP BY
			monitoring.monitoring_ip) monitored_ips
	  LEFT OUTER JOIN monitoring_history
	  ON monitored_ips.monitoring_ip = monitoring_history.history_type
	  WHERE
		monitoring_history.history_section LIKE 'monitoring_%'
	  GROUP BY
		monitoring_history.history_type
	  , monitoring_history.history_section) last_events_list
  LEFT OUTER JOIN monitoring_history
  ON last_events_list.service = monitoring_history.history_section AND last_events_list.ip = monitoring_history.history_type AND last_events_list.last_event = monitoring_history.history_timestamp
  GROUP BY
	monitoring_history.history_type
  , monitoring_history.history_section
  , monitoring_history.history_timestamp
  , monitoring_history.history_new_value
";
			$monitor_query = "select * from monitoring where monitoring_custid='{$custid}'";
		}
		$stats = [];
		$db->query($data_query, __LINE__, __FILE__);
		while ($db->next_record(MYSQL_ASSOC)) {
			if (!isset($stats[$db->Record['ip']])) {
				$stats[$db->Record['ip']] = [];
			}
			$stats[$db->Record['ip']][$db->Record['service']] = ['time' => $db->Record['last_time'], 'status' => $db->Record['status']];
		}
		$monitoring_data = [];
		//print_r($stats['66.23.231.209']);
		$db->query($monitor_query, __LINE__, __FILE__);
		if ($db->num_rows() > 0) {
			while ($db->next_record(MYSQL_ASSOC)) {
				$extra = parse_monitoring_extra($db->Record['monitoring_extra']);
				$monitor = [
					'id' => $db->Record['monitoring_id'],
					'hostname' => $db->Record['monitoring_hostname'],
					'ip' => $db->Record['monitoring_ip'],
					'comment' => $db->Record['monitoring_comment'],
					'extra' => $extra
				];
				if ($GLOBALS['tf']->ima == 'admin') {
					$monitor['custid'] = $db->Record['monitoring_custid'];
				}
				$myservices = [];
				foreach ($services as $service) {
					if (isset($extra[$service]) && $extra[$service] == 1) {
						$myservices[] = $service;
					}
				}
				$monitor['services'] = $myservices;
				if (count($myservices) > 0) {
					foreach ($myservices as $service) {
						if (isset($stats[$db->Record['monitoring_ip']]) && isset($stats[$db->Record['monitoring_ip']][$service])) {
							if ($stats[$db->Record['monitoring_ip']][$service]['status'] == 1) {
								$monitor[$service] = 'Up';
							} else {
								$monitor[$service] = 'Down';
							}
						} else {
							$monitor[$service] = 'Unknown';
						}
					}
				}
				$monitoring_data[] = $monitor;
			}
		}
		return $monitoring_data;
	}

	/**
	 * @return array
	 */
	function get_umonitored_server_list()
	{
		if ($GLOBALS['tf']->ima == 'admin') {
			if (isset($GLOBALS['tf']->variables->request['custid'])) {
				$custid = $db->real_escape($GLOBALS['tf']->variables->request['custid']);
			} else {
				$custid = $GLOBALS['tf']->session->account_id;
			}
		} else {
			$custid = $GLOBALS['tf']->session->account_id;
		}
		$unmatched = [];
		foreach ($GLOBALS['modules'] as $module => $settings) {
			$db = get_module_db($module);
			if (preg_match('/_ip$/', $settings['TITLE_FIELD']) || preg_match('/_ip$/', $settings['TITLE_FIELD'])) {
				//			if ($GLOBALS['tf']->ima == 'admin')
				//			{
				//				$db->query("select * from $settings[TABLE] where $settings[PREFIX]_status='active'");
				//			}
				//			else
				//			{
				$db->query("select * from $settings[TABLE] where $settings[PREFIX]_status='active' and $settings[PREFIX]_custid='" . get_custid($custid, $module) . "'");
				//			}
				if ($db->num_rows() > 0) {
					while ($db->next_record(MYSQL_ASSOC)) {
						$unmatched[] = [
							'section' => $settings['TITLE'],
							'title' => $db->Record[$settings['TITLE_FIELD']],
							'custid' => $db->Record[$settings['PREFIX'].'_custid'],
							'module' => $module,
							'ip' => $db->Record[$settings['PREFIX'].'_ip']
						];
					}
				}
			}
		}
		return $unmatched;
	}

/**
 * @return bool|string
 * @throws \Exception
 * @throws \SmartyException
 */
	function get_umonitored_server_table()
	{
		$unmatched = get_umonitored_server_list();
		if (count($unmatched) > 0) {
			$table = new TFTable;
			$table->set_title('Unmonitored Accounts');
			$table->add_field('Type');
			$table->add_field();
			$table->add_field('Name');
			$table->add_field();
			$table->add_field('Options');
			$table->add_row();
			foreach ($unmatched as $idx => $values) {
				$table->add_field($values['section']);
				$table->add_field();
				$table->add_field($values['title'], 'r');
				$table->add_field();
				$table->add_field($table->make_link('choice=none.monitoring&amp;hostname='.$values['title'].'&amp;ip='.$values['ip'].'&amp;comment='.$values['module'].'&amp;custid='.$values['custid'],
					'Add To Monitoring'));
				$table->add_row();
			}
			return $table->get_table();
		}
		return false;
	}
