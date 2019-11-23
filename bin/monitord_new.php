#!/usr/bin/env php
<?php
/**
* Monitoring Daemon
*
* Insanely efficient, fast, and accurate monitoring daemon
* @package MyAdmin
* @category Monitoring
* @copyright 2019
* @author Joe Huss <detain@interserver.net>
*/

// turn off logging all queries to billingd.log
$GLOBALS['log_queries'] = false;
// set what server to setup database connections from.
//$_SERVER['HTTP_HOST'] = 'my.interserver.net';
$_SERVER['HTTP_HOST'] = 'illuminati.interserver.net';

// Give us eternity to execute the script. We can always kill -9
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');
set_time_limit(0);

require_once __DIR__.'/../../../../include/functions.inc.php';
require_once __DIR__.'/../../../../include/monitoring/monitoring.functions.inc.php';
//require_once(INCLUDE_ROOT.'/billing/billing.functions.inc.php');

global $console;
// remove the console coloring
$console['GREEN'] = '';
$console['WHITE'] = '';
$console['RED'] = '';
$console['BLUE'] = '';

// Do funky things with signals
/**
* @param $signo
*/
function sig_handler($signo)
{
	global $sigterm;
	global $sighup;
	if ($signo == SIGTERM) {
		$sigterm = true;
	} elseif ($signo == SIGHUP) {
		$sighup = true;
	} else {
		echo("Funny signal!\n");
	}
}

declare(ticks = 1);
$wnull = null;
$enull = null;
$max_children = MONITORING_THREADS;
$child = 0;
$children = [];
$max_childrenseen = 0;
$totseen = 0;
$sigterm = false;
$sighup = false;
$started = time();
$tfound = 0;
$response = '';
@ob_end_flush();

$webpage = false;

//	$GLOBALS['tf']->session->create(160307,'services', false);
//	$GLOBALS['tf']->session->verify();

pcntl_signal(SIGTERM, 'sig_handler');
pcntl_signal(SIGHUP, 'sig_handler');
// Fork and exit (daemonize)
/*
$pid = pcntl_fork();
if ($pid == -1)
{
// Not good.
die("There is no fork()!");
}
elseif ($pid)
{
//echo($pid);
exit();
}
*/
$db = new \MyDb\Mysqli\Db($GLOBALS['database_config']['db_name'], $GLOBALS['database_config']['db_user'], $GLOBALS['database_config']['db_pass'], $GLOBALS['database_config']['db_host']);
$db->Type = $GLOBALS['database_config']['db_type'];
$console = [];
//	$dbh = mysql_connect($GLOBALS['database_config']['db_host'], $GLOBALS['database_config']['db_user'], $GLOBALS['database_config']['db_pass'], true);
//	mysql_select_db($GLOBALS['database_config']['db_name'], $dbh);

//	mysql_query("select * from monitoring", $dbh);
$db->query('select * from monitoring', __line__, __file__);
if ($db->num_rows() == 0) {
	echo "$console[RED]Nothing to monitor$console[WHITE]\n";
	exit;
}

// possible services we are able to monitor
$services = [
	'http',
	'smtp',
	'ftp',
	'dns',
	'imap',
	'pop',
	'ssh',
	'ping'
];

$ips = [];
// build an array of all the ips and each service we want to monitor on each.
while ($db->next_record()) {
	if (!validIp($db->Record['monitoring_ip'])) {
		//echo "Invlaid IP $ip , skipping\n";
		continue;
	}
	$new = false;
	if (!isset($ips[$db->Record['monitoring_ip']])) {
		$new = true;
		$ips[$db->Record['monitoring_ip']] = [];
		$ips[$db->Record['monitoring_ip']]['notify'] = [];
	}
	$extra = parse_monitoring_extra($db->Record['monitoring_extra']);
	if (isset($extra['email']) && $extra['email'] != '') {
		$ips[$db->Record['monitoring_ip']]['notify'][$db->Record['monitoring_custid']] = $extra['email'];
	}
	$found = 0;
	foreach ($services as $service) {
		if (isset($extra[$service]) && $extra[$service] == '1') {
			++$found;
			$ips[$db->Record['monitoring_ip']][$service] = 1;
		}
	}
	$tfound += $found;
	if ($new == true && $found == 0) {
		unset($ips[$db->Record['monitoring_ip']]);
	}
}

echo "Monitoring $tfound Services over " . count($ips) . " IPs\n";
//print_r($ips);
$x = 0;
$ip_keys = array_keys($ips);
$offsets = [];
$offset = 0;
foreach ($ips as $ip => $data) {
	$offsets[$ip] = $offset;
	foreach ($services as $service) {
		if (isset($data[$service]) && $data[$service] == '1') {
			++$offset;
		}
	}
}
$shm_size = $offset;
$shm_key = ftok(__FILE__, 'I');
$shm_id = shmop_open($shm_key, 'c', 0644, $shm_size);

$parentpid = posix_getpid();
//print_r($ip_keys);
// loop until someone sends the program a signal
while (!$sighup && !$sigterm) {
	// Patiently wait until some of our children die. Make sure we don't use all powers that be.
	while (pcntl_wait($status, WNOHANG or WUNTRACED) > 0) {
		usleep(1000);
	}
	while (list($key, $val) = each($children)) {
		if (!posix_kill($val, 0)) {
			unset($children[$key]);
			--$child;
		}
	}
	$children = array_values($children);
	if ($child >= $max_children) {
		usleep(1000);
		continue;
	}
	// Wait for somebody to talk to.
	if ($x >= count($ip_keys)) {
		if ($child == 0) {
			$sigterm = true;
		}
		continue;
	}
	$ip = $ip_keys[$x];
	++$x;
	if (!validIp($ip)) {
		//echo "Invlaid IP $ip , breaking\n";
		continue;
	}
	// Fork a child.
	++$child;
	++$totseen;
	if ($child > $max_childrenseen) {
		$max_childrenseen = $child;
	}
	$pid = pcntl_fork();
	if ($pid == -1) {
		// Not good.
		die('There is no fork!');
	}
	if ($pid) {
		// This is the parent. It doesn't do much.
		$children[] = $pid;
		usleep(1000);
	} else {
		$shm_id = shmop_open($shm_key, 'w', 0644, $shm_size);
		$offset = $offsets[$ip];
		//echo "$console[WHITE]Spawned Process $console[BLUE]" . posix_getpid() . "$console[WHITE] To Monitor IP $console[LIGHTBLUE]$ip$console[WHITE]\n";
		$console['GREEN'] = '';
		$console['WHITE'] = '';
		$console['RED'] = '';
		$console['BLUE'] = '';
		$toutput = sprintf('%15s   ', $ip);
		// This is a child. It dies, hopefully.
		foreach ($services as $service) {
			if (isset($ips[$ip][$service]) && $ips[$ip][$service] == 1) {
				$cmd = __DIR__ . "/nagios/check_{$service} -H {$ip} -t 30";
				if ($service == 'ping') {
					$cmd .= ' -w 200.0,80% -c 500.0,100% -p 3';
				}
				if ($service == 'dns') {
					$cmd = __DIR__ . "/nagios/check_{$service} -H 127.0.0.1 -s {$ip}";
				}
				$output = trim(`$cmd`);
				//echo "CMD:$cmd\nOutput:$output\n";
				if (preg_match('/'.mb_strtoupper($service).' OK/', $output) || preg_match('/'.mb_strtoupper($service).' WARNING/', $output)) {
					//echo "	- $console[BROWN]$service	$console[LIGHTGREEN]Good	$console[LIGHTBLUE]$ip$console[WHITE]\n";
					$toutput .= "$service(+) ";
					$status = '1';
				} else {
					//							echo "CMD:$console[RED]$cmd$console[WHITE]\nOutput:$console[LIGHTRED]$output$console[WHITE]\n";
					//							echo "	- $console[BROWN]$service	$console[DARKGRAY]Bad	$console[LIGHTBLUE]$ip$console[WHITE]\n";
					$toutput .= "$service(-)";
					//$toutput .= " $ip $cmd : $output) ";
					$status = '0';
				}
				shmop_write($shm_id, $status, $offset);
				++$offset;
			}
		}
		echo "$toutput\n";
		// Let's die!
		shmop_close($shm_id);
		exit();
	}
}
// Patiently wait until all our children die.
while (pcntl_wait($status, WNOHANG or WUNTRACED) > 0) {
	usleep(5000);
}
// Finally!
foreach ($ips as $ip => $data) {
	$offset = $offsets[$ip];
	foreach ($services as $service) {
		if (isset($data[$service]) && $data[$service] == '1') {
			$status = shmop_read($shm_id, $offset, 1);
			echo "$ip $service $status\n";
			++$offset;
		}
	}
}
echo "Max children seen $max_childrenseen\n";


shmop_delete($shm_id);
shmop_close($shm_id);
exit();
/*
// checking previous history for that ip/service
$result = mysql_query("select * from monitoring_history where history_section='monitoring_$service' and history_type='{$ip}' order by history_id desc limit 3");
$ostatus = '';
$changes = 0;
$depth = 1;
$changed = null;
$ochanged = false;
$unchanged_end = false;
$unchanged_depth = 0;
if (mysql_num_rows($result) > 0)
{
while ($row = mysql_fetch_array($result))
{
$ostatus = $row['history_new_value'];
if (is_null($changed))
{
if ($status != $ostatus)
{
$changed = true;
} else {
$changed = false;
}
$ochanged = $changed;
}
if (!$changed && !$unchanged_end)
{
if ($status == $ostatus)
{
++$unchanged_depth;
} else {
$unchanged_end = true;
}
}
++$depth;
if ($status != $ostatus)
{
++$changes;
}
if ($row['history_new_value'] == $status)
{
++$depth;
}
}
$changed = $ochanged;
}
if ($changes == 0 && $depth == 3)
{
// notify everyone here
}
elseif ($changes == 0 && $depth > 3)
{
// notify people set every
}
if ($status == 1)
{
$tstatus = "Up";
} else {
$tstatus = "Down";
}
if ($changes > 0)
{
if ($status == 1)
{
$tstatus = "Back Up";
} else {
$tstatus = "Down";
}
} else {
if ($status == 1)
{
$tstatus = "Still Up";
} else {
$tstatus = "Still Down";
}
}
if ($changed > 0)
{
$query = make_insert_query('monitoring_history', array(
'history_id' => NULL,
'history_timestamp' => mysql_now(),
'history_section' => 'monitoring_'.$service,
'history_type' => $ip,
'history_new_value' => $status,
'history_old_value' => $response,
));
mysql_query($query);
$toutput .= "(c)";
}
foreach ($ips[$ip]['notify'] as $custid => $email)
{
$data = $GLOBALS['tf']->accounts->read($custid);
if (($changed && $status == 1) || ($status == 0 && $unchanged_depth == 2) || ($status == 0 && $unchanged_depth > 2 && isset($data['notification']) && $data['notification'] == 'every'))
{
$result = mysql_query("select * from monitoring where monitoring_ip='{$ip}' and monitoring_custid='{$custid}'");
$row = mysql_fetch_array($result, MYSQL_ASSOC);
$headers = '';
$headers .= "MIME-Version: 1.0" . PHP_EOL;
$headers .= "Content-Type: text/html; charset=UTF-8" . PHP_EOL;
$headers .= "From: \"My Monitoring\" <monitoring@my.interserver.net>" . PHP_EOL;
$smarty = new TFSmarty;
$smarty->debugging = true;
$smarty->assign('hostname', $row['monitoring_hostname']);
$smarty->assign('url', 'my.interserver.net');
$smarty->assign('ip', $ip);
$smarty->assign('status', $tstatus);
$smarty->assign('service', $service);
$smarty->assign('username', $GLOBALS['tf']->accounts->cross_reference($custid));
if ($row['monitoring_hostname'] != '')
{
$subject = $row['monitoring_hostname'] . " " . $service . " " . $tstatus;
} else {
$subject = "$ip " . $service . " " . $tstatus;
}
$msg = $smarty->fetch('email/client/monitoring.tpl');
(new \MyAdmin\Mail())->clientMail($subject, $msg, $email, 'client/monitoring.tpl');
echo "	- Notified $console[GREEN]$email$console[WHITE]\n";
$toutput .= "(n $email)";
}
}
*/
