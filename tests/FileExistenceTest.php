<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests to verify all expected source files exist in the package.
 */
class FileExistenceTest extends TestCase
{
    /**
     * Test that the Plugin.php source file exists.
     */
    public function testPluginPhpExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/Plugin.php');
    }

    /**
     * Test that monitoring.functions.inc.php exists.
     */
    public function testMonitoringFunctionsFileExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/monitoring.functions.inc.php');
    }

    /**
     * Test that monitoring.php exists.
     */
    public function testMonitoringPhpExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/monitoring.php');
    }

    /**
     * Test that monitoring_setup.php exists.
     */
    public function testMonitoringSetupPhpExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/monitoring_setup.php');
    }

    /**
     * Test that monitoring_stats.php exists.
     */
    public function testMonitoringStatsPhpExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/monitoring_stats.php');
    }

    /**
     * Test that website_scan.php exists.
     */
    public function testWebsiteScanPhpExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/src/website_scan.php');
    }

    /**
     * Test that composer.json exists.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/composer.json');
    }

    /**
     * Test that README.md exists.
     */
    public function testReadmeExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/README.md');
    }

    /**
     * Test that Plugin.php declares the correct namespace.
     */
    public function testPluginPhpHasCorrectNamespace(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $this->assertStringContainsString('namespace Detain\MyAdminMonitoring;', $content);
    }

    /**
     * Test that all source files are valid PHP (no syntax errors detectable by tokenizer).
     */
    public function testAllSourceFilesAreValidPhp(): void
    {
        $srcDir = dirname(__DIR__) . '/src';
        $files = glob($srcDir . '/*.php');
        $this->assertNotEmpty($files, 'Should find PHP files in src/');

        foreach ($files as $file) {
            $tokens = token_get_all(file_get_contents($file));
            $this->assertNotEmpty($tokens, "File {$file} should be tokenizable");
            $this->assertSame(T_OPEN_TAG, $tokens[0][0], "File {$file} should start with PHP open tag");
        }
    }
}
