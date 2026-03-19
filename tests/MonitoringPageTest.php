<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the monitoring page function defined in monitoring.php.
 */
class MonitoringPageTest extends TestCase
{
    /**
     * Test that the monitoring.php source file exists.
     */
    public function testMonitoringFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/monitoring.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Test that the file defines the monitoring function.
     */
    public function testFileDefinesMonitoringFunction(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('function monitoring()', $content);
    }

    /**
     * Test that monitoring function returns false early (disabled feature).
     */
    public function testMonitoringFunctionReturnsEarly(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');

        // The function has "return false;" right at the start
        $pattern = '/function\s+monitoring\s*\(\s*\)\s*\{[^}]*?return\s+false\s*;/s';
        $this->assertMatchesRegularExpression($pattern, $content);
    }

    /**
     * Test that monitoring function references monitoring table operations.
     */
    public function testMonitoringFunctionUsesMonitoringTable(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('monitoring', $content);
        $this->assertStringContainsString('monitoring_id', $content);
    }

    /**
     * Test that monitoring function handles notification settings.
     */
    public function testMonitoringFunctionHandlesNotifications(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('notification', $content);
        $this->assertStringContainsString("'once'", $content);
        $this->assertStringContainsString("'every'", $content);
    }

    /**
     * Test that monitoring function validates IP addresses.
     */
    public function testMonitoringFunctionValidatesIpAddresses(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('validIp', $content);
        $this->assertStringContainsString("'Invalid IP'", $content);
    }

    /**
     * Test that monitoring function uses CSRF protection.
     */
    public function testMonitoringFunctionUsesCsrfProtection(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('verify_csrf', $content);
        $this->assertStringContainsString("csrf('monitoring')", $content);
    }

    /**
     * Test that monitoring function includes delete handling for admin and non-admin.
     */
    public function testMonitoringFunctionHandlesDeleteForBothRoles(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');

        // Should have delete from monitoring in two different contexts
        $deleteCount = substr_count($content, 'delete from monitoring');
        $this->assertGreaterThanOrEqual(2, $deleteCount, 'Should handle delete for both admin and non-admin');
    }

    /**
     * Test that monitoring function uses input sanitization.
     */
    public function testMonitoringFunctionSanitizesInput(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.php');
        $this->assertStringContainsString('strip_tags', $content);
        $this->assertStringContainsString('real_escape', $content);
    }
}
