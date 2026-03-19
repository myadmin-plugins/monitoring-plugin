<?php

declare(strict_types=1);

namespace Detain\MyAdminMonitoring\Tests;

use Detain\MyAdminMonitoring\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class can be instantiated.
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the Plugin class has the expected static $name property.
     */
    public function testPluginHasNameProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('name'));
        $property = $this->reflection->getProperty('name');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('Monitoring Plugin', Plugin::$name);
    }

    /**
     * Test that the Plugin class has the expected static $description property.
     */
    public function testPluginHasDescriptionProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('description'));
        $property = $this->reflection->getProperty('description');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Test that the Plugin class has the expected static $help property.
     */
    public function testPluginHasHelpProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $property = $this->reflection->getProperty('help');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that the Plugin class has the expected static $type property.
     */
    public function testPluginHasTypeProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('type'));
        $property = $this->reflection->getProperty('type');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains the function.requirements key.
     */
    public function testGetHooksContainsFunctionRequirements(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
    }

    /**
     * Test that each hook value is a callable-style array with class and method.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Hook handler for '{$eventName}' should be an array");
            $this->assertCount(2, $handler, "Hook handler for '{$eventName}' should have exactly 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Hook handler class should be Plugin");
            $this->assertIsString($handler[1], "Hook handler method should be a string");
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Plugin class should have method '{$handler[1]}'"
            );
        }
    }

    /**
     * Test that getRequirements hook points to a valid static method.
     */
    public function testGetRequirementsHookPointsToStaticMethod(): void
    {
        $hooks = Plugin::getHooks();
        $handler = $hooks['function.requirements'];
        $method = $this->reflection->getMethod($handler[1]);
        $this->assertTrue($method->isStatic(), 'getRequirements should be a static method');
        $this->assertTrue($method->isPublic(), 'getRequirements should be a public method');
    }

    /**
     * Test that getMenu method exists and accepts GenericEvent parameter.
     */
    public function testGetMenuMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that getRequirements method exists and accepts GenericEvent parameter.
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that getSettings method exists and accepts GenericEvent parameter.
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that the constructor takes no parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that the Plugin class is in the correct namespace.
     */
    public function testPluginNamespace(): void
    {
        $this->assertSame('Detain\MyAdminMonitoring', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the Plugin class is not abstract.
     */
    public function testPluginIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the Plugin class is not an interface.
     */
    public function testPluginIsNotInterface(): void
    {
        $this->assertFalse($this->reflection->isInterface());
    }

    /**
     * Test that getRequirements calls add_page_requirement and add_requirement on the loader.
     */
    public function testGetRequirementsRegistersRequirements(): void
    {
        $pageRequirements = [];
        $requirements = [];

        $loader = new class($pageRequirements, $requirements) {
            /** @var array<int, array{string, string}> */
            private array $pageReqs;
            /** @var array<int, array{string, string}> */
            private array $reqs;

            /**
             * @param array<int, array{string, string}> $pageReqs
             * @param array<int, array{string, string}> $reqs
             */
            public function __construct(array &$pageReqs, array &$reqs)
            {
                $this->pageReqs = &$pageReqs;
                $this->reqs = &$reqs;
            }

            public function add_page_requirement(string $name, string $path): void
            {
                $this->pageReqs[] = [$name, $path];
            }

            public function add_requirement(string $name, string $path): void
            {
                $this->reqs[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $this->assertNotEmpty($pageRequirements, 'Should register page requirements');
        $this->assertNotEmpty($requirements, 'Should register requirements');

        $pageReqNames = array_column($pageRequirements, 0);
        $this->assertContains('monitoring_stats', $pageReqNames);
        $this->assertContains('website_scan', $pageReqNames);
        $this->assertContains('monitoring', $pageReqNames);
        $this->assertContains('monitoring_setup', $pageReqNames);

        $reqNames = array_column($requirements, 0);
        $this->assertContains('get_umonitored_server_table', $reqNames);
        $this->assertContains('get_monitoring_data', $reqNames);
        $this->assertContains('get_monitoring_services', $reqNames);
    }

    /**
     * Test that getSettings does not throw when invoked with a subject.
     */
    public function testGetSettingsDoesNotThrow(): void
    {
        $settings = new \stdClass();
        $event = new GenericEvent($settings);
        Plugin::getSettings($event);
        // No exception means the method executed without error
        $this->assertTrue(true);
    }

    /**
     * Test that all registered requirement paths reference the expected source directory.
     */
    public function testRequirementPathsReferenceSourceDirectory(): void
    {
        $pageRequirements = [];
        $requirements = [];

        $loader = new class($pageRequirements, $requirements) {
            /** @var array<int, array{string, string}> */
            private array $pageReqs;
            /** @var array<int, array{string, string}> */
            private array $reqs;

            /**
             * @param array<int, array{string, string}> $pageReqs
             * @param array<int, array{string, string}> $reqs
             */
            public function __construct(array &$pageReqs, array &$reqs)
            {
                $this->pageReqs = &$pageReqs;
                $this->reqs = &$reqs;
            }

            public function add_page_requirement(string $name, string $path): void
            {
                $this->pageReqs[] = [$name, $path];
            }

            public function add_requirement(string $name, string $path): void
            {
                $this->reqs[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $allPaths = array_merge(
            array_column($pageRequirements, 1),
            array_column($requirements, 1)
        );

        foreach ($allPaths as $path) {
            $this->assertStringContainsString(
                'myadmin-monitoring-plugin/src/',
                $path,
                "Requirement path should reference the plugin src directory"
            );
        }
    }
}
