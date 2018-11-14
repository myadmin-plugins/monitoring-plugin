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
            }
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
		$loader->add_page_requirement('monitoring_stats', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring_stats.php');
		$loader->add_requirement('get_umonitored_server_table', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_requirement('get_monitoring_data', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_page_requirement('website_scan', '/../vendor/detain/myadmin-monitoring-plugin/src/website_scan.php');
		$loader->add_requirement('get_monitoring_services', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring.functions.inc.php');
		$loader->add_page_requirement('monitoring', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring.php');
		$loader->add_page_requirement('monitoring_setup', '/../vendor/detain/myadmin-monitoring-plugin/src/monitoring_setup.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
		$settings->add_radio_setting(__('Billing'), __('Monitoring'), 'paypal_enable', __('Enable Monitoring'), __('Enable Monitoring'), PAYPAL_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_radio_setting(__('Billing'), __('Monitoring'), 'paypal_digitalgoods_enable', __('Enable Digital Goods'), __('Enable Digital Goods'), PAYPAL_DIGITALGOODS_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_email', __('Login / Email '), __('Login / Email '), (defined('PAYPAL_EMAIL') ? PAYPAL_EMAIL : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_api_username', __('API Username'), __('API Username'), (defined('PAYPAL_API_USERNAME') ? PAYPAL_API_USERNAME : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_api_password', __('API Password'), __('API Password'), (defined('PAYPAL_API_PASSWORD') ? PAYPAL_API_PASSWORD : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_api_signature', __('API Signature'), __('API Signature'), (defined('PAYPAL_API_SIGNATURE') ? PAYPAL_API_SIGNATURE : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_sandbox_api_username', __('Sandbox API Username'), __('Sandbox API Username'), (defined('PAYPAL_SANDBOX_API_USERNAME') ? PAYPAL_SANDBOX_API_USERNAME : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_sandbox_api_password', __('Sandbox API Password'), __('Sandbox API Password'), (defined('PAYPAL_SANDBOX_API_PASSWORD') ? PAYPAL_SANDBOX_API_PASSWORD : ''));
		$settings->add_text_setting(__('Billing'), __('Monitoring'), 'paypal_sandbox_api_signature', __('Sandbox API Signature'), __('Sandbox API Signature'), (defined('PAYPAL_SANDBOX_API_SIGNATURE') ? PAYPAL_SANDBOX_API_SIGNATURE : ''));
	}
}
