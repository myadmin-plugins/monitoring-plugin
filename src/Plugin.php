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
    }
}
