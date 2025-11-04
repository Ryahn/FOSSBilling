<?php

declare(strict_types=1);

namespace ImageproxyTests;

use Box\Mod\Imageproxy\Service;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * Unit tests for the Imageproxy Service class.
 * These tests focus on the whitelist functionality and image proxification logic.
 */
final class ServiceTest extends TestCase
{
    private Service $service;
    private Container $di;

    protected function setUp(): void
    {
        $this->service = new Service();
        $this->di = new Container();
    }

    /**
     * Test that proxifyImages works with an empty whitelist (proxy everything).
     */
    public function testProxifyImagesWithEmptyWhitelist(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => ''];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        $content = '![test](https://example.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should be proxified since whitelist is empty
        $this->assertStringContainsString('/imageproxy/image', $result);
    }

    /**
     * Test that proxifyImages skips whitelisted hosts.
     */
    public function testProxifyImagesWithWhitelistedHost(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "imgur.com\nexample.com"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test with whitelisted host
        $content = '![test](https://example.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified since example.com is whitelisted
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://example.com/image.png', $result);
    }

    /**
     * Test that proxifyImages proxies non-whitelisted hosts.
     */
    public function testProxifyImagesWithNonWhitelistedHost(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "imgur.com\nexample.com"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test with non-whitelisted host
        $content = '![test](https://other.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should be proxified since other.com is not whitelisted
        $this->assertStringContainsString('/imageproxy/image', $result);
    }

    /**
     * Test wildcard matching for subdomains.
     */
    public function testProxifyImagesWithWildcardWhitelist(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "*.imgur.com"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test subdomain matching
        $content = '![test](https://i.imgur.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified since i.imgur.com matches *.imgur.com
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://i.imgur.com/image.png', $result);
    }

    /**
     * Test that wildcards also match the base domain.
     */
    public function testProxifyImagesWithWildcardMatchesBaseDomain(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "*.imgur.com"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test that wildcard also matches the base domain
        $content = '![test](https://imgur.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified since imgur.com matches *.imgur.com
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://imgur.com/image.png', $result);
    }

    /**
     * Test HTML img tags with whitelisted hosts.
     */
    public function testProxifyImagesWithHtmlTags(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => 'example.com'];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test HTML img tag with whitelisted host
        $content = '<img src="https://example.com/image.png" alt="test">';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://example.com/image.png', $result);
    }

    /**
     * Test case-insensitive host matching.
     */
    public function testProxifyImagesCaseInsensitive(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => 'example.com'];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test case insensitive matching
        $content = '![test](https://EXAMPLE.COM/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified since matching is case-insensitive
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://EXAMPLE.COM/image.png', $result);
    }

    /**
     * Test multiple images with mixed whitelisted and non-whitelisted hosts.
     */
    public function testProxifyImagesWithMultipleHosts(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "imgur.com\npicsum.photos"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test multiple images - some whitelisted, some not
        $content = '![img1](https://imgur.com/1.png) ![img2](https://other.com/2.png) ![img3](https://picsum.photos/200)';
        $result = $this->service->proxifyImages($content);

        // imgur.com and picsum.photos should NOT be proxified
        $this->assertStringContainsString('https://imgur.com/1.png)', $result);
        $this->assertStringContainsString('https://picsum.photos/200)', $result);

        // other.com should be proxified
        $this->assertStringContainsString('/imageproxy/image', $result);
    }

    /**
     * Test that whitespace in config is handled correctly.
     */
    public function testProxifyImagesWithWhitespaceInConfig(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => "  imgur.com  \n\n  example.com  \n  \n"];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test that whitespace is properly trimmed
        $content = '![test](https://imgur.com/image.png)';
        $result = $this->service->proxifyImages($content);

        // Should NOT be proxified even with whitespace in config
        $this->assertStringNotContainsString('/imageproxy/image', $result);
        $this->assertStringContainsString('https://imgur.com/image.png', $result);
    }

    /**
     * Test that already proxified URLs are not double-proxified.
     */
    public function testProxifyImagesDoesNotDoubleProxy(): void
    {
        $this->di['mod_config'] = $this->di->protect(function ($modName) {
            if ($modName === 'imageproxy') {
                return ['whitelisted_hosts' => ''];
            }

            return [];
        });

        $urlMock = $this->createMock(\stdClass::class);
        $urlMock->method('link')->willReturn('http://localhost/imageproxy/image?u=encoded');
        $this->di['url'] = $urlMock;

        $this->service->setDi($this->di);

        // Test with already proxified URL
        $content = '![test](http://localhost/imageproxy/image?u=someencoded)';
        $result = $this->service->proxifyImages($content);

        // Should not be double-proxified
        $this->assertEquals($content, $result);
    }
}
