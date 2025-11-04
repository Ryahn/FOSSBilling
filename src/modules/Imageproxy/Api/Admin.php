<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Imageproxy\Api;

/**
 * Image Proxy Admin API.
 *
 * Handles administrative API endpoints for the Image Proxy module.
 * Note: Image serving is handled directly in Controller, not API layer,
 * to avoid session issues with binary content.
 */
class Admin extends \Api_Abstract
{
    /**
     * Migrate all existing ticket messages to use proxified image URLs.
     * This is a one-time operation to retroactively apply image proxy to old tickets.
     *
     * @return array{processed: int, updated: int, images_found: int} Migration statistics
     */
    public function migrate_existing_tickets(): array
    {
        $service = $this->getService();

        return $service->migrateExistingTickets();
    }

    /**
     * Revert all proxified image URLs back to their original URLs.
     * Useful if you need to disable the module temporarily or before uninstalling.
     *
     * @return array{processed: int, reverted: int} Reversion statistics
     */
    public function revert_proxified_urls(): array
    {
        $service = $this->getService();

        return $service->revertAllProxifiedUrls();
    }
}
