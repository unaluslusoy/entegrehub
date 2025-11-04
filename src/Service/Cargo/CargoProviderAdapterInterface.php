<?php

namespace App\Service\Cargo;

use App\Entity\CargoCompany;
use App\Entity\Shipment;

/**
 * Interface for cargo provider API adapters
 *
 * Each cargo company will have its own adapter implementing this interface.
 * This allows for standardized API integration across different providers.
 */
interface CargoProviderAdapterInterface
{
    /**
     * Test API connection
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(CargoCompany $company): array;

    /**
     * Create a shipment via API
     *
     * @return array [
     *   'success' => bool,
     *   'tracking_number' => string|null,
     *   'label_url' => string|null,
     *   'message' => string,
     * ]
     */
    public function createShipment(Shipment $shipment): array;

    /**
     * Track a shipment
     *
     * @return array [
     *   'success' => bool,
     *   'status' => string|null,
     *   'history' => array,
     *   'message' => string,
     * ]
     */
    public function trackShipment(Shipment $shipment): array;

    /**
     * Cancel a shipment
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelShipment(Shipment $shipment, string $reason): array;
}
