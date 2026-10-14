<?php

declare(strict_types=1);

namespace Omi\Tests\Unit;

use Omi\Config\ArrayConfigSource;
use Omi\Config\BoolParser;
use Omi\Config\OmiConfig;
use PHPUnit\Framework\TestCase;

final class OmiConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new OmiConfig(new ArrayConfigSource());

        self::assertTrue($config->sslVerify());
        self::assertFalse($config->automaticLogin());
        self::assertFalse($config->forceSync());
        self::assertFalse($config->deviceErrorListEnabled());
        self::assertFalse($config->returnCustomerEmail());
        self::assertSame('?enabled=1', $config->onuLinkParams());
        self::assertSame('', $config->oltManagerUrl());
        self::assertFalse($config->isOltManagerConfigured());
        self::assertSame([], $config->nodeAccessConfigurationIgnoreGroups());
        self::assertNull($config->nodeAccessConfigurationAt());
        self::assertSame('', $config->integrationInstanceParam());
        self::assertSame(100, $config->deviceErrorListPageLimit());
    }

    public function testStringFalseIsFalse(): void
    {
        // dawny błąd: omi.olt_manager_force_sync = "false" było traktowane jako prawda
        $config = new OmiConfig(new ArrayConfigSource([
            'omi.olt_manager_force_sync' => 'false',
            'omi.ssl_verify' => 'nie',
            'omi.olt_manager_automatic_login' => 'TAK',
        ]));

        self::assertFalse($config->forceSync());
        self::assertFalse($config->sslVerify());
        self::assertTrue($config->automaticLogin());
    }

    public function testBoolParserMatchesLmsCheckValue(): void
    {
        foreach (['1', 'y', 'on', 'yes', 'true', 'tak', 't', 'enabled', 'TRUE', true, 1] as $value) {
            self::assertTrue(BoolParser::parse($value, false), var_export($value, true));
        }
        foreach (['0', 'n', 'no', 'off', 'false', 'nie', 'disabled', false, 0] as $value) {
            self::assertFalse(BoolParser::parse($value, true), var_export($value, true));
        }
        self::assertTrue(BoolParser::parse('garbage', true));
        self::assertFalse(BoolParser::parse(null, false));
    }

    public function testUrlAndLists(): void
    {
        $config = new OmiConfig(new ArrayConfigSource([
            'omi.olt_manager_url' => ' https://om.example.com/ ',
            'omi.olt_manager_token' => 'secret',
            'omi.node_access_configuration_ignore_groups' => '3, 1,x,3,,0',
            'omi.node_access_configuration_at' => 'null',
        ]));

        self::assertSame('https://om.example.com', $config->oltManagerUrl());
        self::assertTrue($config->isOltManagerConfigured());
        self::assertSame([3, 1], $config->nodeAccessConfigurationIgnoreGroups());
        self::assertNull($config->nodeAccessConfigurationAt());
    }

    public function testIntegrationInstancePrefersId(): void
    {
        $config = new OmiConfig(new ArrayConfigSource([
            'omi.integration_slug' => 'lms v2',
            'omi.integration_instance_id' => '7',
        ]));
        self::assertSame('integrationInstanceId=7', $config->integrationInstanceParam());

        $config = new OmiConfig(new ArrayConfigSource(['omi.integration_slug' => 'lms v2', 'omi.integration_instance_id' => 'null']));
        self::assertSame('integrationSlug=lms%20v2', $config->integrationInstanceParam());
    }

    public function testEveryDefinitionHasValidDefault(): void
    {
        foreach (OmiConfig::DEFINITIONS as $name => $definition) {
            self::assertMatchesRegularExpression('/^[a-z_]+$/', $name);
            self::assertNotSame('', $definition['description']);
            if ($definition['type'] === OmiConfig::TYPE_BOOL) {
                self::assertContains($definition['default'], ['true', 'false'], $name);
            }
        }
    }
}
