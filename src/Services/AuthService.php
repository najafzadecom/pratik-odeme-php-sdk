<?php

namespace PratikOdeme\Services;

use PratikOdeme\Exceptions\PratikOdemeException;

/**
 * Authentication Service
 * 
 * Giriş və doğrulama əməliyyatları üçün servis
 */
class AuthService extends BaseService
{
    /**
     * Login to API
     *
     * @param string $username
     * @param string $password
     * @param string $dealerCode
     * @param string|null $branchCode
     * @return array
     * @throws PratikOdemeException
     */
    public function login(string $username, string $password, string $dealerCode, ?string $branchCode = null): array
    {
        $this->validateRequired([
            'username' => $username,
            'password' => $password,
            'dealerCode' => $dealerCode,
        ], ['username', 'password', 'dealerCode']);

        // Hash password with SHA512
        $hashedPassword = hash('sha512', $password);

        $data = [
            'UserName' => $username,
            'Password' => $hashedPassword,
            'DealerCode' => $dealerCode,
        ];

        if ($branchCode !== null) {
            $data['BranchCode'] = $branchCode;
        }

        $response = $this->makeRequest('merchantapi/login', $data);

        // Set access token if login successful
        if (isset($response['Token'])) {
            $this->client->setAccessToken($response['Token']);
        }

        return $response;
    }

    /**
     * Create secret key
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function createSecretKey(): array
    {
        $data = [
            'thisStep' => 'Secret_Key_Send'
        ];

        $response = $this->makeRequest('merchantapi/my_secret_key', $data);

        // Set secret key if available in headers or response
        if (isset($response['_headers']['secretkey'])) {
            $this->client->setSecretKey($response['_headers']['secretkey']);
        } elseif (isset($response['secretKey'])) {
            $this->client->setSecretKey($response['secretKey']);
        }

        return $response;
    }

    /**
     * Create confirm key
     *
     * @param string $confirmKey Max 10 karakter, numeric
     * @return array
     * @throws PratikOdemeException
     */
    public function createConfirmKey(string $confirmKey): array
    {
        $this->validateRequired(['confirmKey' => $confirmKey], ['confirmKey']);

        // Validate confirm key format
        if (!is_numeric($confirmKey) || strlen($confirmKey) > 10) {
            throw new PratikOdemeException('Confirm key must be numeric and max 10 characters');
        }

        $data = [
            'thisStep' => 'Confirm_Key_Send',
            'confirmKey' => $confirmKey
        ];

        $response = $this->makeRequest('merchantapi/my_confirm_key', $data);

        return $response;
    }

    /**
     * Get error code list
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function getErrorCodeList(): array
    {
        $data = [
            'thisStep' => 'Error Code List'
        ];

        return $this->makeRequest('merchantapi/error_code_list', $data);
    }

    /**
     * Logout (clear stored tokens)
     *
     * @return void
     */
    public function logout(): void
    {
        $this->client->setAccessToken('');
        $this->client->setSecretKey('');
    }
}