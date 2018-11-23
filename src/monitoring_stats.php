<?php
	/**
	* Administrative Functionality
	* @author Joe Huss <detain@interserver.net>
	* @copyright 2019
	* @package MyAdmin
	* @category Admin
	*/

	/**
	 * monitoring_stats
	 * returns an array of affiliate stats in the format of:
	 * Array(
	 * 		[2013-05-20] => Array(
	 * 			[total] => 16
	 * 			[rejected] => 12
	 * 			[failed] => 4
	 * 		)
	 * 		[2013-05-21] => Array(
	 * 			[total] => 26
	 * 			[default] => 1
	 * 			[paid] => 1
	 * 			[rejected] => 10
	 * 			[locked] => 6
	 * 			[failed] => 8
	 * 		)
	 * )
	 * @return array array of signups by date in Y-m-d format
	 */
	function monitoring_stats_data()
	{
		$db = $GLOBALS['tf']->db;
		function_requirements('has_acl');
		if ($GLOBALS['tf']->ima == 'admin' && has_acl('client_billing')) {
			if (isset($GLOBALS['tf']->variables->request['custid'])) {
				$custid = (int)$GLOBALS['tf']->variables->request['custid'];
				$query = "select monitoring_status, (select history_timestamp from monitoring_history where history_owner=monitoring_custid limit 1) as signup_date from affiliates where monitoring_owner={$custid} order by signup_date";
			} else {
				$query = 'select monitoring_status, (select history_timestamp from monitoring_history where history_owner=monitoring_custid limit 1) as signup_date from affiliates where monitoring_owner > 0 order by signup_date';
			}
		} else {
			$custid = $GLOBALS['tf']->session->account_id;
			$query = "select monitoring_status, (select history_timestamp from monitoring_history where history_owner=monitoring_custid limit 1) as signup_date from affiliates where monitoring_owner={$custid} order by signup_date";
		}
		$db->query($query, __LINE__, __FILE__);
		$stats = [
			'default' => [],
			'pending' => [],
			'verified' => [],
			'paid' => [],
			'failed' => [],
			'locked' => [],
			'duplicate' => [],
			'rejected' => []
		];
		while ($db->next_record(MYSQL_ASSOC)) {
			$db->Record['signup_date'] = date('Y-m', $db->fromTimestamp($db->Record['signup_date']));
			if (!isset($stats[$db->Record['monitoring_status']][$db->Record['signup_date']])) {
				$stats[$db->Record['monitoring_status']][$db->Record['signup_date']] = 1;
			} else {
				$stats[$db->Record['monitoring_status']][$db->Record['signup_date']]++;
			}
		}
		return $stats;
	}

	function monitoring_stats()
	{
		$echart_dir = 'echarts';
		$echart_path = "/bower_components/{$echart_dir}";
		add_js('font-awesome');
		add_js('flot');
		add_js('bootstrap');
		add_js('requirejs');
		add_js('echarts');
		page_title('Monitoring Statistics');
		$module = get_module_name((isset($GLOBALS['tf']->variables->request['module']) ? $GLOBALS['tf']->variables->request['module'] : 'default'));
		$settings = \get_module_settings($module);
		$db = get_module_db($module);
		$stats = monitoring_stats_data();
		//_debug_array($stats);
		$stats_json = [];
		$stats_js = '{';
		$earliest_date = date('Y-m');
		foreach ($stats as $status => $stats_data) {
			foreach ($stats_data as $stats_date => $stats_count) {
				if ($stats_date < $earliest_date) {
					$earliest_date = $stats_date;
				}
			}
		}
		foreach ($stats as $status => $stats_data) {
			$stats_json[$status] = [];
			$stats_js .= "'{$status}': [";
			foreach ($stats_data as $stats_date => $stats_count) {
				$stats_year = mb_substr($stats_date, 0, 4);
				$stats_month = mb_substr($stats_date, 5, 2);
				$stats_js .= "[new Date({$stats_year},{$stats_month}), {$stats_count}, {$stats_count}],";
				$stats_json[$status][] = [$stats_date.'-01 01:01:01', $stats_count, $stats_count];
				//$stats_json[$status][] = array($stats_date.'-01 01:01:01', $stats_count);
			}
			if (mb_substr($stats_js, mb_strlen($stats_js) - 1) == ',') {
				$stats_js = mb_substr($stats_js, 0, mb_strlen($stats_js) - 1);
			}
			$stats_js .= "],\n";
		}
		if (mb_substr($stats_js, mb_strlen($stats_js) - 1) == ',') {
			$stats_js = mb_substr($stats_js, 0, mb_strlen($stats_js) - 1);
		}
		$stats_js .= '}';
		$graph = 1;
		$code = file_get_contents(INCLUDE_ROOT.'/stats/invoice_payments.js');
		$enable_shrink = false;
		$smarty = new TFSmarty();
		$smarty->assign('echart_path', $echart_path);
		$smarty->assign('echart_dir', $echart_dir);
		$smarty->assign('www_type_js', (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"'));
		$smarty->assign('enable_shrink', $enable_shrink);
		$smarty->assign('code', $code);
		//$smarty->assign('stats', json_encode($stats_json));
		$smarty->assign('stats', $stats_js);
		$GLOBALS['tf']->add_html_head_css_file('/css/echarts-carousel.css');
		$GLOBALS['tf']->add_html_head_css_file('/css/echarts.css');
		//$GLOBALS['tf']->add_html_head_js_file($echart_path.'/doc/asset/js/esl/esl.js');
		//add_output($smarty->fetch('echarts/echarts_editor.tpl'));
		add_output($smarty->fetch('echarts/echarts_monitoring.tpl'));
	}
