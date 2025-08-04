<?php

namespace PratikOdeme\Services;

use PratikOdeme\PratikOdemeClient;
use PratikOdeme\Exceptions\PratikOdemeException;

/**
 * Base Service Class
 * 
 * Bütün servis siniflərinin əsas sinfi
 */
abstract class BaseService
{
    /**
     * @var PratikOdemeClient
     */
    protected PratikOdemeClient $client;

    /**
     * Constructor
     *
     * @param PratikOdemeClient $client
     */
    public function __construct(PratikOdemeClient $client)
    {
        $this->client = $client;
    }

    /**
     * Make API request and handle response
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     * @throws PratikOdemeException
     */
    protected function makeRequest(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = $this->client->makeRequest($endpoint, $data, $headers);
        
        // Check if response indicates success
        if (isset($response['Success']) && $response['Success'] === false) {
            throw PratikOdemeException::fromApiResponse($response);
        }

        return $response;
    }

    /**
     * Validate required parameters
     *
     * @param array $data
     * @param array $required
     * @throws PratikOdemeException
     */
    protected function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                throw new PratikOdemeException("Required field '{$field}' is missing or empty");
            }
        }
    }

    /**
     * Format amount to API format (multiply by 100)
     *
     * @param float $amount
     * @return int
     */
    protected function formatAmount(float $amount): int
    {
        return (int)($amount * 100);
    }

    /**
     * Parse amount from API format (divide by 100)
     *
     * @param int|string $amount
     * @return float
     */
    protected function parseAmount($amount): float
    {
        return (float)$amount / 100;
    }
}