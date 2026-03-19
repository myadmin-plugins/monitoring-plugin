<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the website_scan function defined in website_scan.php.
 */
class WebsiteScanTest extends TestCase
{
    /**
     * Test that the website_scan.php source file exists.
     */
    public function testWebsiteScanFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/website_scan.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Test that the file defines the website_scan function.
     */
    public function testFileDefinesWebsiteScanFunction(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('function website_scan()', $content);
    }

    /**
     * Test that website_scan creates a form with a website input field.
     */
    public function testWebsiteScanHasWebsiteInput(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString("'website'", $content);
        $this->assertStringContainsString("'Website To Scan'", $content);
    }

    /**
     * Test that website_scan has a Scan submit button.
     */
    public function testWebsiteScanHasScanButton(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString("make_submit('Scan')", $content);
    }

    /**
     * Test that website_scan uses Sucuri site check API.
     */
    public function testWebsiteScanUsesSucuriApi(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('sitecheck.sucuri.net', $content);
    }

    /**
     * Test that website_scan properly encodes the URL for the API call.
     */
    public function testWebsiteScanEncodesUrl(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('urlencode', $content);
    }

    /**
     * Test that website_scan handles BLACKLIST data differently from other sections.
     */
    public function testWebsiteScanHandlesBlacklistSpecially(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString("'BLACKLIST'", $content);

        // The BLACKLIST branch accesses $value[0] and $value[1] differently
        $this->assertStringContainsString('$value[0]', $content);
        $this->assertStringContainsString('$value[1]', $content);
    }

    /**
     * Test that website_scan uses TFTable for rendering.
     */
    public function testWebsiteScanUsesTFTable(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('new TFTable()', $content);
    }

    /**
     * Test that website_scan uses getcurlpage for HTTP requests.
     */
    public function testWebsiteScanUsesGetcurlpage(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('getcurlpage(', $content);
    }

    /**
     * Test that website_scan uses myadmin_unstringify to deserialize response.
     */
    public function testWebsiteScanDeserializesResponse(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/website_scan.php');
        $this->assertStringContainsString('myadmin_unstringify(', $content);
    }
}
