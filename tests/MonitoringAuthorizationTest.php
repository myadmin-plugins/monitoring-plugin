<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Executable tests for the admin/non-admin split in the monitoring data layer.
 *
 * get_monitoring_data() is the live implementation of the rule the monitoring pages
 * are supposed to follow: an admin sees every monitored host, a client sees only
 * rows belonging to their own account. This is the security-relevant behaviour that
 * MonitoringSetupTest used to try to cover by grepping the page source for the text
 * "ima == 'admin'" — a check that proved nothing about enforcement and broke as soon
 * as the code moved to \MyAdmin\App::ima().
 *
 * These tests run the function against a recording database and assert on the SQL it
 * actually issues and the fields it actually returns.
 */
class MonitoringAuthorizationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/Stubs.php';
        require_once dirname(__DIR__).'/src/monitoring.functions.inc.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        FrameworkState::reset();
        RecordingAccounts::$updates = [];
    }

    /**
     * A client's monitoring listing is scoped to their own account: every query that
     * reads the monitoring table carries their custid, and the unscoped listing query
     * an admin gets is never issued.
     */
    public function testClientListingIsScopedToTheirOwnAccount(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$accountId = 4242;

        get_monitoring_data();

        $monitoringQueries = RecordingDb::queriesMatching('monitoring');
        $this->assertNotEmpty($monitoringQueries, 'get_monitoring_data() should query the monitoring tables');

        $this->assertNotContains(
            'select * from monitoring',
            RecordingDb::$queries,
            'a client must never receive the unscoped admin listing query'
        );
        $this->assertContains(
            "select * from monitoring where monitoring_custid='4242'",
            RecordingDb::$queries,
            "a client's listing query must be scoped to their own custid"
        );
        $this->assertSame(
            1,
            count(RecordingDb::queriesMatching("monitoring.monitoring_custid='4242'")),
            "the client's status-history query must also be scoped to their own custid"
        );
    }

    /**
     * An admin's listing is deliberately unscoped: they see every monitored host.
     */
    public function testAdminListingIsNotScopedToOneAccount(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$accountId = 4242;

        get_monitoring_data();

        $this->assertContains(
            'select * from monitoring',
            RecordingDb::$queries,
            'an admin should get the unscoped monitoring listing'
        );
        $this->assertSame(
            [],
            RecordingDb::queriesMatching('monitoring_custid='),
            'an admin listing should not be constrained to a single custid'
        );
    }

    /**
     * A client cannot widen their own scope by passing custid in the request. The
     * non-admin branch must take the custid from the session, never from user input,
     * otherwise any client could read another customer's monitored hosts.
     */
    public function testClientCannotEscalateScopeWithRequestCustid(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$accountId = 4242;
        FrameworkState::$request = ['custid' => '999'];

        get_monitoring_data();

        foreach (RecordingDb::$queries as $sql) {
            $this->assertStringNotContainsString(
                '999',
                $sql,
                'a client-supplied custid must never reach the monitoring queries'
            );
        }
        $this->assertContains(
            "select * from monitoring where monitoring_custid='4242'",
            RecordingDb::$queries,
            "the client's own session custid must be used instead"
        );
    }

    /**
     * An admin may pass custid to look at one customer, and that scopes the listing.
     */
    public function testAdminMayScopeListingToARequestedCustomer(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$request = ['custid' => '777'];

        get_monitoring_data();

        // The admin listing itself stays unscoped, but the requested customer is what
        // the account lookup and history scoping key off.
        $this->assertContains(
            'select * from monitoring',
            RecordingDb::$queries,
            'an admin still receives the full monitoring listing'
        );
    }

    /**
     * The owning custid of each monitored host is an admin-only field: it must not be
     * present in the records handed to a client.
     */
    public function testOwningCustidIsExposedToAdminsOnly(): void
    {
        $monitoringRow = [
            'monitoring_id' => '7',
            'monitoring_hostname' => 'host.example.com',
            'monitoring_ip' => '10.0.0.9',
            'monitoring_comment' => 'a comment',
            'monitoring_extra' => '',
            'monitoring_custid' => '4242',
        ];

        FrameworkState::$ima = 'client';
        FrameworkState::$accountId = 4242;
        RecordingDb::$rowsFor = ['select * from monitoring' => [$monitoringRow]];

        $clientData = get_monitoring_data();

        $this->assertCount(1, $clientData);
        $this->assertSame('host.example.com', $clientData[0]['hostname']);
        $this->assertArrayNotHasKey(
            'custid',
            $clientData[0],
            'the owning custid is an admin-only field and must not be returned to a client'
        );

        FrameworkState::reset();
        FrameworkState::$ima = 'admin';
        RecordingDb::$rowsFor = ['select * from monitoring' => [$monitoringRow]];

        $adminData = get_monitoring_data();

        $this->assertCount(1, $adminData);
        $this->assertArrayHasKey(
            'custid',
            $adminData[0],
            'an admin should see which account owns each monitored host'
        );
        $this->assertSame('4242', $adminData[0]['custid']);
    }

    /**
     * Only services the customer opted into are reported, and their up/down state is
     * derived from the latest monitoring_history row for that ip and service.
     */
    public function testOnlyOptedInServicesAreReportedWithTheirStatus(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$accountId = 4242;
        RecordingDb::$rowsFor = [
            'history_new_value AS status' => [
                ['ip' => '10.0.0.9', 'service' => 'ping', 'last_time' => '100', 'status' => '1'],
                ['ip' => '10.0.0.9', 'service' => 'http', 'last_time' => '101', 'status' => '0'],
            ],
            'select * from monitoring' => [[
                'monitoring_id' => '7',
                'monitoring_hostname' => 'host.example.com',
                'monitoring_ip' => '10.0.0.9',
                'monitoring_comment' => '',
                'monitoring_extra' => json_encode(['ping' => 1, 'http' => 1, 'ssh' => 0]),
                'monitoring_custid' => '4242',
            ]],
        ];

        $data = get_monitoring_data();

        $this->assertCount(1, $data);
        $this->assertSame(['ping', 'http'], $data[0]['services'], 'only opted-in services are monitored');
        $this->assertSame('Up', $data[0]['ping'], 'latest history value 1 means Up');
        $this->assertSame('Down', $data[0]['http'], 'latest history value 0 means Down');
        $this->assertArrayNotHasKey('ssh', $data[0], 'a service that was not opted into is not reported');
    }

    /**
     * A monitored host with no history yet reports Unknown rather than claiming Down.
     */
    public function testServiceWithNoHistoryReportsUnknown(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$accountId = 4242;
        RecordingDb::$rowsFor = [
            'select * from monitoring' => [[
                'monitoring_id' => '8',
                'monitoring_hostname' => 'fresh.example.com',
                'monitoring_ip' => '10.0.0.10',
                'monitoring_comment' => '',
                'monitoring_extra' => json_encode(['ping' => 1]),
                'monitoring_custid' => '4242',
            ]],
        ];

        $data = get_monitoring_data();

        $this->assertSame('Unknown', $data[0]['ping']);
    }
}
