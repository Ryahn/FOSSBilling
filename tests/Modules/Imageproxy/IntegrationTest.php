<?php

declare(strict_types=1);

namespace ImageproxyTests;

use APIHelper\Request;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Imageproxy module.
 * These tests require a running FOSSBilling instance and make actual API calls.
 */
final class IntegrationTest extends TestCase
{
    /**
     * Test that the module can be configured via the API.
     */
    public function testModuleConfiguration(): void
    {
        // Test updating configuration using standard extension config_save
        $result = Request::makeRequest('admin/extension/config_save', [
            'ext' => 'mod_imageproxy',
            'max_size_mb' => 10,
            'timeout_seconds' => 7,
            'max_duration_seconds' => 15,
        ]);

        $this->assertTrue($result->wasSuccessful(), $result->generatePHPUnitMessage());
        $this->assertTrue($result->getResult());

        // Verify config was saved
        $configResult = Request::makeRequest('admin/extension/config_get', [
            'ext' => 'mod_imageproxy',
        ]);

        $this->assertTrue($configResult->wasSuccessful(), $configResult->generatePHPUnitMessage());
        $config = $configResult->getResult();
        $this->assertEquals(10, $config['max_size_mb']);
        $this->assertEquals(7, $config['timeout_seconds']);
        $this->assertEquals(15, $config['max_duration_seconds']);

        // Reset to defaults
        Request::makeRequest('admin/extension/config_save', [
            'ext' => 'mod_imageproxy',
            'max_size_mb' => 5,
            'timeout_seconds' => 5,
            'max_duration_seconds' => 10,
        ]);
    }

    /**
     * Test that whitelisted hosts can be configured.
     */
    public function testWhitelistConfiguration(): void
    {
        // Test updating configuration with whitelist using standard endpoint
        $result = Request::makeRequest('admin/extension/config_save', [
            'ext' => 'mod_imageproxy',
            'max_size_mb' => 5,
            'timeout_seconds' => 5,
            'max_duration_seconds' => 10,
            'whitelisted_hosts' => "imgur.com\npicsum.photos\nexample.com",
        ]);

        $this->assertTrue($result->wasSuccessful(), $result->generatePHPUnitMessage());
        $this->assertTrue($result->getResult());

        // Verify config was saved
        $configResult = Request::makeRequest('admin/extension/config_get', [
            'ext' => 'mod_imageproxy',
        ]);

        $this->assertTrue($configResult->wasSuccessful(), $configResult->generatePHPUnitMessage());
        $config = $configResult->getResult();
        $this->assertArrayHasKey('whitelisted_hosts', $config);
        $this->assertStringContainsString('imgur.com', $config['whitelisted_hosts']);
        $this->assertStringContainsString('picsum.photos', $config['whitelisted_hosts']);
        $this->assertStringContainsString('example.com', $config['whitelisted_hosts']);

        // Reset whitelist
        Request::makeRequest('admin/extension/config_save', [
            'ext' => 'mod_imageproxy',
            'max_size_mb' => 5,
            'timeout_seconds' => 5,
            'max_duration_seconds' => 10,
            'whitelisted_hosts' => '',
        ]);
    }

    /**
     * Test that empty whitelist is handled correctly.
     */
    public function testEmptyWhitelist(): void
    {
        $result = Request::makeRequest('admin/extension/config_save', [
            'ext' => 'mod_imageproxy',
            'max_size_mb' => 5,
            'timeout_seconds' => 5,
            'max_duration_seconds' => 10,
            'whitelisted_hosts' => '',
        ]);

        $this->assertTrue($result->wasSuccessful(), $result->generatePHPUnitMessage());

        // Verify empty whitelist was saved
        $configResult = Request::makeRequest('admin/extension/config_get', [
            'ext' => 'mod_imageproxy',
        ]);

        $this->assertTrue($configResult->wasSuccessful(), $configResult->generatePHPUnitMessage());
        $config = $configResult->getResult();
        // Whitelist may or may not be in config if empty, both are valid
        $this->assertTrue(!isset($config['whitelisted_hosts']) || $config['whitelisted_hosts'] === '');
    }

    /**
     * Test migration of existing tickets to use proxified URLs.
     */
    public function testMigrateExistingTickets(): void
    {
        $result = Request::makeRequest('admin/imageproxy/migrate_existing_tickets');

        $this->assertTrue($result->wasSuccessful(), $result->generatePHPUnitMessage());
        $stats = $result->getResult();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('processed', $stats);
        $this->assertArrayHasKey('updated', $stats);
        $this->assertArrayHasKey('images_found', $stats);
        $this->assertGreaterThanOrEqual(0, $stats['processed']);
        $this->assertGreaterThanOrEqual(0, $stats['updated']);
        $this->assertGreaterThanOrEqual(0, $stats['images_found']);
    }

    /**
     * Test reversion of proxified URLs back to originals.
     */
    public function testRevertProxifiedUrls(): void
    {
        $result = Request::makeRequest('admin/imageproxy/revert_proxified_urls');

        $this->assertTrue($result->wasSuccessful(), $result->generatePHPUnitMessage());
        $stats = $result->getResult();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('processed', $stats);
        $this->assertArrayHasKey('reverted', $stats);
        $this->assertGreaterThanOrEqual(0, $stats['processed']);
        $this->assertGreaterThanOrEqual(0, $stats['reverted']);
    }
}
