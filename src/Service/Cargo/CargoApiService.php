<?php

namespace App\Service\Cargo;

use App\Entity\CargoCompany;
use App\Entity\CargoProvider;
use App\Entity\Shipment;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CargoApiService - Centralized API integration for cargo companies
 *
 * This service handles API connections to various cargo providers.
 * Replaces TODO comments in controllers with actual implementation.
 */
class CargoApiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Test connection to a cargo company API
     */
    public function testConnection(CargoCompany $company): array
    {
        try {
            $credentials = $company->getCredentials();

            if (!$credentials || empty($credentials)) {
                return [
                    'success' => false,
                    'message' => 'No credentials configured',
                ];
            }

            // Get provider-specific adapter
            $adapter = $this->getAdapter($company->getCode());

            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'No adapter available for ' . $company->getName(),
                ];
            }

            $result = $adapter->testConnection($company);

            $this->logger->info('Cargo API connection test', [
                'company' => $company->getCode(),
                'result' => $result['success'] ? 'success' : 'failed',
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Cargo API connection test failed', [
                'company' => $company->getCode(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection to a cargo provider (for user integrations)
     */
    public function testProviderConnection(CargoProvider $provider, array $credentials): array
    {
        try {
            $adapter = $this->getAdapter($provider->getCode());

            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'No adapter available for ' . $provider->getName(),
                ];
            }

            // Create temporary company object for testing
            $tempCompany = new CargoCompany();
            $tempCompany->setCode($provider->getCode());
            $tempCompany->setName($provider->getName());
            $tempCompany->setApiUrl($provider->getApiEndpoint());
            $tempCompany->setCredentials($credentials);

            $result = $adapter->testConnection($tempCompany);

            $this->logger->info('Cargo provider connection test', [
                'provider' => $provider->getCode(),
                'result' => $result['success'] ? 'success' : 'failed',
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Cargo provider connection test failed', [
                'provider' => $provider->getCode(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a shipment via cargo company API
     */
    public function createShipment(Shipment $shipment): array
    {
        try {
            $company = $shipment->getCargoCompany();
            if (!$company) {
                throw new \InvalidArgumentException('No cargo company assigned to shipment');
            }

            $adapter = $this->getAdapter($company->getCode());

            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'No adapter available for ' . $company->getName(),
                ];
            }

            $result = $adapter->createShipment($shipment);

            $this->logger->info('Shipment created via API', [
                'shipment_id' => $shipment->getId(),
                'company' => $company->getCode(),
                'tracking_number' => $result['tracking_number'] ?? null,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Cargo API shipment creation failed', [
                'shipment_id' => $shipment->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Shipment creation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Track a shipment via cargo company API
     */
    public function trackShipment(Shipment $shipment): array
    {
        try {
            $company = $shipment->getCargoCompany();
            if (!$company) {
                throw new \InvalidArgumentException('No cargo company assigned to shipment');
            }

            $adapter = $this->getAdapter($company->getCode());

            if (!$adapter) {
                // Return fallback tracking info
                return [
                    'success' => false,
                    'message' => 'Tracking not available for ' . $company->getName(),
                    'tracking_url' => $company->getTrackingUrlForNumber($shipment->getTrackingNumber() ?? ''),
                ];
            }

            $result = $adapter->trackShipment($shipment);

            $this->logger->info('Shipment tracked via API', [
                'shipment_id' => $shipment->getId(),
                'company' => $company->getCode(),
                'status' => $result['status'] ?? null,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Cargo API tracking failed', [
                'shipment_id' => $shipment->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Tracking failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a shipment via cargo company API
     */
    public function cancelShipment(Shipment $shipment, string $reason): array
    {
        try {
            $company = $shipment->getCargoCompany();
            if (!$company) {
                throw new \InvalidArgumentException('No cargo company assigned to shipment');
            }

            $adapter = $this->getAdapter($company->getCode());

            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'Cancellation not available via API for ' . $company->getName(),
                ];
            }

            $result = $adapter->cancelShipment($shipment, $reason);

            $this->logger->info('Shipment cancelled via API', [
                'shipment_id' => $shipment->getId(),
                'company' => $company->getCode(),
                'reason' => $reason,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Cargo API cancellation failed', [
                'shipment_id' => $shipment->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Cancellation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get provider-specific adapter
     */
    private function getAdapter(string $code): ?CargoProviderAdapterInterface
    {
        // TODO: Implement factory pattern to return provider-specific adapters
        // For now, return null - adapters will be implemented in separate classes

        // Example of what this will look like:
        // return match ($code) {
        //     'yurtici' => new YurticiCargoAdapter($this->httpClient),
        //     'mng' => new MngCargoAdapter($this->httpClient),
        //     'aras' => new ArasCargoAdapter($this->httpClient),
        //     'ptt' => new PttCargoAdapter($this->httpClient),
        //     'surat' => new SuratCargoAdapter($this->httpClient),
        //     'ups' => new UpsCargoAdapter($this->httpClient),
        //     default => null,
        // };

        $this->logger->warning('Cargo adapter not implemented', [
            'code' => $code,
            'message' => 'Provider adapters need to be implemented',
        ]);

        return null;
    }
}
