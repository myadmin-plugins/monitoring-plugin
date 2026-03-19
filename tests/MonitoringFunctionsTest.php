<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the global monitoring functions defined in monitoring.functions.inc.php.
 */
class MonitoringFunctionsTest extends TestCase
{
    /**
     * Test that the source file monitoring.functions.inc.php exists.
     */
    public function testMonitoringFunctionsFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/monitoring.functions.inc.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Test that the source file defines the parse_monitoring_extra function.
     */
    public function testFileDefinesParseMonitoringExtra(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString('function parse_monitoring_extra(', $content);
    }

    /**
     * Test that the source file defines the get_monitoring_services function.
     */
    public function testFileDefinesGetMonitoringServices(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString('function get_monitoring_services(', $content);
    }

    /**
     * Test that the source file defines the get_monitoring_data function.
     */
    public function testFileDefinesGetMonitoringData(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString('function get_monitoring_data(', $content);
    }

    /**
     * Test that the source file defines the get_umonitored_server_list function.
     */
    public function testFileDefinesGetUmonitoredServerList(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString('function get_umonitored_server_list(', $content);
    }

    /**
     * Test that the source file defines the get_umonitored_server_table function.
     */
    public function testFileDefinesGetUmonitoredServerTable(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString('function get_umonitored_server_table(', $content);
    }

    /**
     * Test that get_monitoring_services returns the expected list of services.
     */
    public function testGetMonitoringServicesReturnsList(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $expectedServices = ['ping', 'http', 'smtp', 'ftp', 'dns', 'imap', 'pop', 'ssh'];

        foreach ($expectedServices as $service) {
            $this->assertStringContainsString(
                "'{$service}'",
                $content,
                "get_monitoring_services should include '{$service}'"
            );
        }
    }

    /**
     * Test that get_monitoring_services defines exactly 8 services.
     */
    public function testGetMonitoringServicesCountByStaticAnalysis(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        // Extract the function body for get_monitoring_services
        $pattern = '/function\s+get_monitoring_services\s*\(\s*\)\s*\{(.*?)\}/s';
        $this->assertMatchesRegularExpression($pattern, $content);

        preg_match($pattern, $content, $matches);
        $functionBody = $matches[1];

        // Count the quoted strings in the array definition
        preg_match_all("/'\w+'/", $functionBody, $services);
        $this->assertCount(8, $services[0], 'get_monitoring_services should define exactly 8 services');
    }

    /**
     * Test that parse_monitoring_extra handles empty string by static analysis.
     */
    public function testParseMonitoringExtraHandlesEmptyString(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        // Verify it checks for empty string and returns empty array
        $this->assertStringContainsString("\$extra == ''", $content);
        $this->assertStringContainsString('return []', $content);
    }

    /**
     * Test that parse_monitoring_extra validates array return type by static analysis.
     */
    public function testParseMonitoringExtraValidatesArrayReturn(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        // Verify it checks if the result is an array
        $this->assertStringContainsString('is_array($ret)', $content);
    }

    /**
     * Test that get_monitoring_data uses proper SQL structure for admin queries.
     */
    public function testGetMonitoringDataUsesProperSqlStructure(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        // Verify the function uses monitoring and monitoring_history tables
        $this->assertStringContainsString('monitoring_history', $content);
        $this->assertStringContainsString('monitoring_history.history_section', $content);
        $this->assertStringContainsString('monitoring_history.history_type', $content);
        $this->assertStringContainsString('monitoring_history.history_timestamp', $content);
    }

    /**
     * Test that get_monitoring_data builds monitor records with expected keys.
     */
    public function testGetMonitoringDataBuildsMonitorRecords(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        $expectedKeys = ['id', 'hostname', 'ip', 'comment', 'extra', 'services'];
        foreach ($expectedKeys as $key) {
            $this->assertStringContainsString(
                "'{$key}'",
                $content,
                "Monitor record should include key '{$key}'"
            );
        }
    }

    /**
     * Test that get_monitoring_data maps status values to Up/Down/Unknown strings.
     */
    public function testGetMonitoringDataMapsStatusValues(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        $this->assertStringContainsString("'Up'", $content);
        $this->assertStringContainsString("'Down'", $content);
        $this->assertStringContainsString("'Unknown'", $content);
    }

    /**
     * Test that get_umonitored_server_list checks for _ip suffix in TITLE_FIELD.
     */
    public function testGetUmonitoredServerListChecksIpSuffix(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString("'/_ip$/'", $content);
    }

    /**
     * Test that get_umonitored_server_list builds unmatched records with expected keys.
     */
    public function testGetUmonitoredServerListBuildsRecords(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');

        $expectedKeys = ['section', 'title', 'custid', 'module', 'ip'];
        foreach ($expectedKeys as $key) {
            $this->assertStringContainsString(
                "'{$key}'",
                $content,
                "Unmonitored server record should include key '{$key}'"
            );
        }
    }

    /**
     * Test that get_umonitored_server_table filters by active status.
     */
    public function testGetUmonitoredServerTableFiltersActiveStatus(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
        $this->assertStringContainsString("_status='active'", $content);
    }
}
