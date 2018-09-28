<?php

namespace Detain\MyAdminMonitoring;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminMonitoring
 */
class Plugin
{
	public static $name = 'Monitoring Plugin';
	public static $description = 'Allows handling of Monitoring based Payments through their Payment Processor/Payment System.';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			//'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
			'function.requirements' => [__CLASS__, 'getRequirements']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
			if (has_acl('client_billing')) {
				$menu->add_link('admin', 'choice=none.abuse_admin', '/lib/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Monitoring');
			}
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		$loader = $event->getSubject();
		$loader->add_page_requirement('monitoring_stats', '/../vendor/detain/monitoring-plugin/src/monitoring_stats.php');
		$loader->add_requirement('get_umonitored_server_table', '/../vendor/detain/monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_requirement('get_monitoring_data', '/../vendor/detain/monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_page_requirement('website_scan', '/../vendor/detain/monitoring-plugin/src/website_scan.php');
		$loader->add_requirement('get_monitoring_services', '/../vendor/detain/monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_page_requirement('monitoring', '/../vendor/detain/monitoring-plugin/src/monitoring.php');
		$loader->add_page_requirement('monitoring_setup', '/../vendor/detain/monitoring-plugin/src/monitoring_setup.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		$settings = $event->getSubject();
		$settings->add_radio_setting('Billing', 'Monitoring', 'paypal_enable', 'Enable Monitoring', 'Enable Monitoring', PAYPAL_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_radio_setting('Billing', 'Monitoring', 'paypal_digitalgoods_enable', 'Enable Digital Goods', 'Enable Digital Goods', PAYPAL_DIGITALGOODS_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_email', 'Login / Email ', 'Login / Email ', (defined('PAYPAL_EMAIL') ? PAYPAL_EMAIL : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_api_username', 'API Username', 'API Username', (defined('PAYPAL_API_USERNAME') ? PAYPAL_API_USERNAME : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_api_password', 'API Password', 'API Password', (defined('PAYPAL_API_PASSWORD') ? PAYPAL_API_PASSWORD : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_api_signature', 'API Signature', 'API Signature', (defined('PAYPAL_API_SIGNATURE') ? PAYPAL_API_SIGNATURE : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_sandbox_api_username', 'Sandbox API Username', 'Sandbox API Username', (defined('PAYPAL_SANDBOX_API_USERNAME') ? PAYPAL_SANDBOX_API_USERNAME : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_sandbox_api_password', 'Sandbox API Password', 'Sandbox API Password', (defined('PAYPAL_SANDBOX_API_PASSWORD') ? PAYPAL_SANDBOX_API_PASSWORD : ''));
		$settings->add_text_setting('Billing', 'Monitoring', 'paypal_sandbox_api_signature', 'Sandbox API Signature', 'Sandbox API Signature', (defined('PAYPAL_SANDBOX_API_SIGNATURE') ? PAYPAL_SANDBOX_API_SIGNATURE : ''));
	}
}
