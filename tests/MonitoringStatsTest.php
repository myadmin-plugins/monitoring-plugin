<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the monitoring stats functions defined in monitoring_stats.php.
 */
class MonitoringStatsTest extends TestCase
{
    /**
     * Test that the monitoring_stats.php source file exists.
     */
    public function testMonitoringStatsFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/monitoring_stats.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Test that the file defines the monitoring_stats_data function.
     */
    public function testFileDefinesMonitoringStatsDataFunction(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('function monitoring_stats_data()', $content);
    }

    /**
     * Test that the file defines the monitoring_stats function.
     */
    public function testFileDefinesMonitoringStatsFunction(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('function monitoring_stats()', $content);
    }

    /**
     * Test that monitoring_stats_data initializes the expected status categories.
     */
    public function testMonitoringStatsDataInitializesStatusCategories(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');

        $expectedStatuses = ['default', 'pending', 'verified', 'paid', 'failed', 'locked', 'duplicate', 'rejected'];
        foreach ($expectedStatuses as $status) {
            $this->assertStringContainsString(
                "'{$status}'",
                $content,
                "Stats data should initialize status category '{$status}'"
            );
        }
    }

    /**
     * Test that monitoring_stats_data has exactly 8 status categories.
     */
    public function testMonitoringStatsDataHasCorrectStatusCount(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');

        // Extract the $stats array initialization
        $pattern = '/\$stats\s*=\s*\[(.*?)\]\s*;/s';
        preg_match($pattern, $content, $matches);
        $this->assertNotEmpty($matches, 'Should find $stats array initialization');

        // Count the status keys
        preg_match_all("/'(\w+)'\s*=>\s*\[\]/", $matches[1], $statusKeys);
        $this->assertCount(8, $statusKeys[1], 'Should have exactly 8 status categories');
    }

    /**
     * Test that monitoring_stats_data checks ACL for admin access.
     */
    public function testMonitoringStatsDataChecksAcl(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('has_acl', $content);
        $this->assertStringContainsString("'client_billing'", $content);
    }

    /**
     * Test that monitoring_stats_data formats dates as Y-m.
     */
    public function testMonitoringStatsDataFormatsDateAsYearMonth(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString("date('Y-m'", $content);
    }

    /**
     * Test that monitoring_stats loads required JavaScript libraries.
     */
    public function testMonitoringStatsLoadsJsLibraries(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');

        $expectedLibraries = ['font-awesome', 'flot', 'bootstrap', 'requirejs', 'echarts'];
        foreach ($expectedLibraries as $lib) {
            $this->assertStringContainsString(
                "'{$lib}'",
                $content,
                "monitoring_stats should load JS library '{$lib}'"
            );
        }
    }

    /**
     * Test that monitoring_stats uses Smarty template rendering.
     */
    public function testMonitoringStatsUsesSmartyTemplate(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('TFSmarty', $content);
        $this->assertStringContainsString('echarts/echarts_monitoring.tpl', $content);
    }

    /**
     * Test that monitoring_stats assigns expected Smarty variables.
     */
    public function testMonitoringStatsAssignsSmartyVariables(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');

        $expectedVars = ['echart_path', 'echart_dir', 'enable_shrink', 'code', 'stats'];
        foreach ($expectedVars as $var) {
            $this->assertStringContainsString(
                "assign('{$var}'",
                $content,
                "monitoring_stats should assign Smarty variable '{$var}'"
            );
        }
    }

    /**
     * Test that monitoring_stats adds CSS files for eCharts.
     */
    public function testMonitoringStatsAddsCssFiles(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('echarts-carousel.css', $content);
        $this->assertStringContainsString('echarts.css', $content);
    }

    /**
     * Test that monitoring_stats uses mb_substr for multibyte-safe string operations.
     */
    public function testMonitoringStatsUsesMultibyteFunctions(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring_stats.php');
        $this->assertStringContainsString('mb_substr', $content);
        $this->assertStringContainsString('mb_strlen', $content);
    }
}
