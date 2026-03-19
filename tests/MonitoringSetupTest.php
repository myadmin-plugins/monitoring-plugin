<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the monitoring_setup page function defined in monitoring_setup.php.
 */
class MonitoringSetupTest extends TestCase
{
    /**
     * Test that the monitoring_setup.php source file exists.
     */
    public function testMonitoringSetupFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/monitoring_setup.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Test that the file defines the monitoring_setup function.
     */
    public function testFileDefinesMonitoringSetupFunction(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('function monitoring_setup()', $content);
    }

    /**
     * Test that monitoring_setup function returns false early (disabled feature).
     */
    public function testMonitoringSetupReturnsEarly(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');

        $pattern = '/function\s+monitoring_setup\s*\(\s*\)\s*\{[^}]*?return\s+false\s*;/s';
        $this->assertMatchesRegularExpression($pattern, $content);
    }

    /**
     * Test that monitoring_setup uses CSRF protection.
     */
    public function testMonitoringSetupUsesCsrfProtection(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('verify_csrf', $content);
        $this->assertStringContainsString("csrf('monitoring_setup')", $content);
    }

    /**
     * Test that monitoring_setup renders form fields for hostname, IP, comment, email.
     */
    public function testMonitoringSetupRendersExpectedFormFields(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');

        $expectedFields = ['Hostname', 'IP', 'Comment', 'Email To'];
        foreach ($expectedFields as $field) {
            $this->assertStringContainsString(
                "'{$field}'",
                $content,
                "Monitoring setup should render field '{$field}'"
            );
        }
    }

    /**
     * Test that monitoring_setup renders each monitoring service as a form option.
     */
    public function testMonitoringSetupUsesGetMonitoringServices(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('get_monitoring_services()', $content);
    }

    /**
     * Test that monitoring_setup uses radio buttons for service selection.
     */
    public function testMonitoringSetupUsesRadioButtons(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('type="radio"', $content);
    }

    /**
     * Test that monitoring_setup shows service status as Up/Down/dash.
     */
    public function testMonitoringSetupShowsServiceStatus(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString("'Up'", $content);
        $this->assertStringContainsString("'Down'", $content);
        $this->assertStringContainsString("'-'", $content);
    }

    /**
     * Test that monitoring_setup queries monitoring_history for status.
     */
    public function testMonitoringSetupQueriesHistory(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('monitoring_history', $content);
        $this->assertStringContainsString('history_new_value', $content);
    }

    /**
     * Test that monitoring_setup uses htmlspecial for output escaping.
     */
    public function testMonitoringSetupEscapesOutput(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString('htmlspecial(', $content);
    }

    /**
     * Test that monitoring_setup differentiates admin and non-admin queries.
     */
    public function testMonitoringSetupDifferentiatesAdminQueries(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_setup.php');
        $this->assertStringContainsString("ima == 'admin'", $content);
        $this->assertStringContainsString('monitoring_custid', $content);
    }
}
