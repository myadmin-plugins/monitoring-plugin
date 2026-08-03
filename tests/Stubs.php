<?php

/**
 * Test doubles for the MyAdmin framework globals that the procedural monitoring
 * pages and helpers depend on, so their behaviour can be EXECUTED in a test rather
 * than grepped for in the source text.
 *
 * detain/myadmin-plugin-installer already supplies the real global
 * function_requirements(), get_service_define() and get_module_db() via composer's
 * "files" autoloader, and those delegate to \MyAdmin\App — so the App stub below is
 * what they resolve against and the code under test runs through the genuine shims.
 * Every definition is guarded so this file is safe to include from several test
 * classes.
 */

namespace Detain\MyAdminMonitoring\Tests {
    /**
     * Mutable state shared with the framework stubs defined below.
     */
    final class FrameworkState
    {
        /** Value returned by \MyAdmin\App::ima(). */
        public static string $ima = 'client';

        /** Account id reported by \MyAdmin\App::session()->account_id. */
        public static int $accountId = 4242;

        /** @var array<string, mixed> request parameters seen by App::variables()->request */
        public static array $request = [];

        /** @var array<string, mixed> account row returned by App::accounts()->read() */
        public static array $account = ['account_lid' => 'user@example.com'];

        /** @var list<string> everything handed to add_output() */
        public static array $output = [];

        /** @var list<string> everything handed to page_title() */
        public static array $titles = [];

        /** @var list<string> every name passed through function_requirements() */
        public static array $requirements = [];

        public static function reset(): void
        {
            self::$ima = 'client';
            self::$accountId = 4242;
            self::$request = [];
            self::$account = ['account_lid' => 'user@example.com'];
            self::$output = [];
            self::$titles = [];
            self::$requirements = [];
            RecordingDb::reset();
        }

        /** All captured page output as one string. */
        public static function outputText(): string
        {
            return implode("\n", self::$output);
        }
    }

    /**
     * Recording stand-in for a MyAdmin database handle.
     *
     * The monitoring code clones its handle (`clone \MyAdmin\App::db()`), so the query
     * log is static: every clone records to the same place.
     */
    final class RecordingDb
    {
        /** @var list<string> every SQL string passed to query() */
        public static array $queries = [];

        /** @var list<array<string, mixed>> rows every query() hands back by default */
        public static array $rows = [];

        /**
         * Rows to hand back only for queries containing a given substring, so the two
         * different SELECTs a page issues can return their own shapes.
         *
         * @var array<string, list<array<string, mixed>>> needle => rows
         */
        public static array $rowsFor = [];

        /** @var array<string, mixed>|false current row */
        public $Record = false;

        /** @var list<array<string, mixed>> */
        private $pending = [];

        public static function reset(): void
        {
            self::$queries = [];
            self::$rows = [];
            self::$rowsFor = [];
        }

        public function query($sql, $line = 0, $file = '')
        {
            $sql = (string) $sql;
            self::$queries[] = $sql;
            $this->pending = self::$rows;
            foreach (self::$rowsFor as $needle => $rows) {
                if (str_contains($sql, (string) $needle)) {
                    $this->pending = $rows;
                    break;
                }
            }
            $this->Record = false;
            return true;
        }

        public function num_rows()
        {
            return count($this->pending);
        }

        public function next_record($mode = null)
        {
            if ($this->pending === []) {
                $this->Record = false;
                return false;
            }
            $this->Record = array_shift($this->pending);
            return true;
        }

        public function real_escape($value)
        {
            return is_string($value) ? addslashes($value) : $value;
        }

        public function getLastInsertId($table = '', $column = '')
        {
            return 1;
        }

        public function qr($sql)
        {
            self::$queries[] = (string) $sql;
            return false;
        }

        /** All recorded queries whose text mentions the given table. */
        public static function queriesMatching(string $needle): array
        {
            return array_values(array_filter(
                self::$queries,
                static fn (string $sql): bool => str_contains($sql, $needle)
            ));
        }
    }

    /**
     * Accounts stand-in: read() returns the fixture row, update() records the change.
     */
    final class RecordingAccounts
    {
        /** @var list<array{custid: mixed, data: array}> */
        public static array $updates = [];

        public function read($custid)
        {
            return FrameworkState::$account;
        }

        public function update($custid, $data)
        {
            self::$updates[] = ['custid' => $custid, 'data' => $data];
            return true;
        }
    }

    /**
     * Session stand-in exposing the account_id the non-admin path scopes on.
     */
    final class StubSession
    {
        public function __get($name)
        {
            if ($name === 'account_id') {
                return FrameworkState::$accountId;
            }
            return null;
        }

        public function appsession($key, $value = null)
        {
            return null;
        }

        public function appnocache($key, $value = null)
        {
            return null;
        }
    }

    /**
     * Request-variables stand-in ($request is a public array in the real class too).
     */
    final class StubVariables
    {
        /** @var array<string, mixed> */
        public $request = [];
    }
}

namespace MyAdmin {
    if (!\class_exists(App::class, false)) {
        /**
         * Minimal stand-in for \MyAdmin\App exposing only the statics the monitoring
         * code and the plugin-installer global shims reach for.
         */
        class App
        {
            /** @return string */
            public static function ima()
            {
                return \Detain\MyAdminMonitoring\Tests\FrameworkState::$ima;
            }

            /** @return \Detain\MyAdminMonitoring\Tests\RecordingDb */
            public static function db()
            {
                return new \Detain\MyAdminMonitoring\Tests\RecordingDb();
            }

            /** @return \Detain\MyAdminMonitoring\Tests\StubSession */
            public static function session()
            {
                return new \Detain\MyAdminMonitoring\Tests\StubSession();
            }

            /** @return \Detain\MyAdminMonitoring\Tests\RecordingAccounts */
            public static function accounts()
            {
                return new \Detain\MyAdminMonitoring\Tests\RecordingAccounts();
            }

            /** @return \Detain\MyAdminMonitoring\Tests\StubVariables */
            public static function variables()
            {
                $variables = new \Detain\MyAdminMonitoring\Tests\StubVariables();
                $variables->request = \Detain\MyAdminMonitoring\Tests\FrameworkState::$request;
                return $variables;
            }

            /**
             * Backs the real global function_requirements().
             *
             * @param string|array $function
             * @return bool
             */
            public static function functionRequirements($function)
            {
                if (!\is_array($function)) {
                    \Detain\MyAdminMonitoring\Tests\FrameworkState::$requirements[] = (string) $function;
                }
                return true;
            }

            /**
             * Backs the real global get_service_define().
             *
             * @param string $service
             * @return string
             */
            public static function getServiceDefine($service)
            {
                return 'define.'.$service;
            }

            /**
             * @param string $class
             * @return bool
             */
            public static function has($class)
            {
                return false;
            }
        }
    }
}

namespace {
    use Detain\MyAdminMonitoring\Tests\FrameworkState;

    if (!defined('MYSQL_ASSOC')) {
        define('MYSQL_ASSOC', 1);
    }

    if (!function_exists('page_title')) {
        function page_title($title)
        {
            FrameworkState::$titles[] = (string) $title;
        }
    }

    if (!function_exists('add_output')) {
        function add_output($output)
        {
            FrameworkState::$output[] = (string) $output;
        }
    }

    if (!function_exists('myadmin_log')) {
        function myadmin_log($module, $level, $message, $line = 0, $file = '', ...$rest)
        {
        }
    }

    if (!function_exists('myadmin_unstringify')) {
        function myadmin_unstringify($data)
        {
            $decoded = json_decode((string) $data, true);
            return $decoded === null ? [] : $decoded;
        }
    }

    if (!function_exists('myadmin_stringify')) {
        function myadmin_stringify($data)
        {
            return json_encode($data);
        }
    }
}
