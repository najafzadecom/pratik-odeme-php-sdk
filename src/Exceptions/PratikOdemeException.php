<?php

namespace PratikOdeme\Exceptions;

use Exception;

/**
 * Pratik Ödeme Exception
 * 
 * API ilə əlaqəli xətalar üçün exception sinfi
 */
class PratikOdemeException extends Exception
{
    /**
     * @var string|null Response code from API
     */
    private ?string $responseCode = null;

    /**
     * @var array|null Response data from API
     */
    private ?array $responseData = null;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param string|null $responseCode
     * @param array|null $responseData
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Exception $previous = null,
        ?string $responseCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseCode = $responseCode;
        $this->responseData = $responseData;
    }

    /**
     * Get API response code
     *
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return $this->responseCode;
    }

    /**
     * Get API response data
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Create exception from API response
     *
     * @param array $response
     * @return static
     */
    public static function fromApiResponse(array $response): self
    {
        $message = $response['ResponseDescription'] ?? 'Unknown API error';
        $responseCode = $response['ResponseCode'] ?? null;
        
        return new self($message, 0, null, $responseCode, $response);
    }
}