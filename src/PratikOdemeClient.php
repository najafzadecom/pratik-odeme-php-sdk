<?php

namespace PratikOdeme;

use PratikOdeme\Exceptions\PratikOdemeException;
use PratikOdeme\Services\AuthService;
use PratikOdeme\Services\AccountService;
use PratikOdeme\Services\TransactionService;
use PratikOdeme\Services\ReportService;

/**
 * Pratik Ödeme API Client
 * 
 * Pratik Ödeme Kurumsal Cüzdan API-si üçün PHP kütüphanəsi
 * 
 * @version 1.0.0
 * @author Your Name
 */
class PratikOdemeClient
{
    /**
     * @var string API base URL
     */
    private string $baseUrl;

    /**
     * @var string Channel ID
     */
    private string $channelId;

    /**
     * @var string|null Access token
     */
    private ?string $accessToken = null;

    /**
     * @var string|null Secret key
     */
    private ?string $secretKey = null;

    /**
     * @var AuthService
     */
    public AuthService $auth;

    /**
     * @var AccountService
     */
    public AccountService $account;

    /**
     * @var TransactionService
     */
    public TransactionService $transaction;

    /**
     * @var ReportService
     */
    public ReportService $report;

    /**
     * Constructor
     *
     * @param string $baseUrl API base URL
     * @param string $channelId Channel ID
     */
    public function __construct(string $baseUrl, string $channelId)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->channelId = $channelId;

        // Initialize services
        $this->auth = new AuthService($this);
        $this->account = new AccountService($this);
        $this->transaction = new TransactionService($this);
        $this->report = new ReportService($this);
    }

    /**
     * Make HTTP request to API
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     * @throws PratikOdemeException
     */
    public function makeRequest(string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Default headers
        $defaultHeaders = [
            'Content-Type: application/json',
            'channelID: ' . $this->channelId,
        ];

        // Add access token if available
        if ($this->accessToken) {
            $defaultHeaders[] = 'accessToken: ' . $this->accessToken;
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new PratikOdemeException('cURL Error: ' . $error);
        }

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if ($httpCode !== 200) {
            throw new PratikOdemeException('HTTP Error: ' . $httpCode);
        }

        $responseData = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PratikOdemeException('JSON Decode Error: ' . json_last_error_msg());
        }

        // Parse headers for special values (like secretKey, confirmKey, smsCode)
        $parsedHeaders = $this->parseHeaders($headers);
        if (!empty($parsedHeaders)) {
            $responseData['_headers'] = $parsedHeaders;
        }

        return $responseData;
    }

    /**
     * Parse response headers for special values
     *
     * @param string $headers
     * @return array
     */
    private function parseHeaders(string $headers): array
    {
        $parsedHeaders = [];
        $headerLines = explode("\r\n", $headers);
        
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim(strtolower($key));
                $value = trim($value);
                
                // Special headers that we need to capture
                if (in_array($key, ['secretkey', 'confirmkey', 'smscode', 'smsmessage'])) {
                    $parsedHeaders[$key] = $value;
                }
            }
        }
        
        return $parsedHeaders;
    }

    /**
     * Generate hash key for transactions
     *
     * @param array $data
     * @return string
     * @throws PratikOdemeException
     */
    public function generateHashKey(array $data): string
    {
        if (!$this->secretKey) {
            throw new PratikOdemeException('Secret key is required for hash generation');
        }

        $dataString = implode(':', $data) . ':' . $this->secretKey;
        return hash('sha256', $dataString);
    }

    /**
     * Set access token
     *
     * @param string $token
     * @return void
     */
    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    /**
     * Get access token
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Set secret key
     *
     * @param string $key
     * @return void
     */
    public function setSecretKey(string $key): void
    {
        $this->secretKey = $key;
    }

    /**
     * Get secret key
     *
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get channel ID
     *
     * @return string
     */
    public function getChannelId(): string
    {
        return $this->channelId;
    }
}