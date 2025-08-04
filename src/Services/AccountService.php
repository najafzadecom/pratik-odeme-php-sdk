<?php

namespace PratikOdeme\Services;

use PratikOdeme\Exceptions\PratikOdemeException;

/**
 * Account Service
 * 
 * Hesab idarəçiliyi əməliyyatları üçün servis
 */
class AccountService extends BaseService
{
    /**
     * Get account basic information
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function getAccountBasic(): array
    {
        $data = [
            'thisStep' => 'My_Account_Basic'
        ];

        return $this->makeRequest('merchantapi/my_account_basic', $data);
    }

    /**
     * Get account balance
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function getBalance(): array
    {
        $data = [
            'thisStep' => 'My_Balance'
        ];

        $response = $this->makeRequest('merchantapi/my_balance', $data);

        // Parse amounts in response
        if (isset($response['walletInfo']) && is_array($response['walletInfo'])) {
            foreach ($response['walletInfo'] as &$wallet) {
                if (isset($wallet['totalBalance'])) {
                    $wallet['totalBalanceFormatted'] = $this->parseAmount($wallet['totalBalance']);
                }
                if (isset($wallet['unavailableBalance'])) {
                    $wallet['unavailableBalanceFormatted'] = $this->parseAmount($wallet['unavailableBalance']);
                }
            }
        }

        return $response;
    }

    /**
     * Save IBAN
     *
     * @param string $iban
     * @param string $accountHolderName
     * @return array
     * @throws PratikOdemeException
     */
    public function saveIban(string $iban, string $accountHolderName): array
    {
        $this->validateRequired([
            'iban' => $iban,
            'accountHolderName' => $accountHolderName,
        ], ['iban', 'accountHolderName']);

        $data = [
            'iban' => $iban,
            'accountHolderName' => $accountHolderName
        ];

        return $this->makeRequest('merchantapi/my_iban_save', $data);
    }

    /**
     * Update IBAN
     *
     * @param int $ibanId
     * @param int $status 0 = delete, 1 = activate
     * @return array
     * @throws PratikOdemeException
     */
    public function updateIban(int $ibanId, int $status): array
    {
        $this->validateRequired([
            'ibanId' => $ibanId,
            'status' => $status,
        ], ['ibanId', 'status']);

        if (!in_array($status, [0, 1])) {
            throw new PratikOdemeException('Status must be 0 (delete) or 1 (activate)');
        }

        $data = [
            'ibanId' => $ibanId,
            'status' => $status
        ];

        return $this->makeRequest('merchantapi/my_iban_update', $data);
    }

    /**
     * Get IBAN list
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function getIbanList(): array
    {
        $data = [
            'thisStep' => 'My_Iban'
        ];

        return $this->makeRequest('merchantapi/my_iban', $data);
    }

    /**
     * Delete IBAN
     *
     * @param int $ibanId
     * @return array
     * @throws PratikOdemeException
     */
    public function deleteIban(int $ibanId): array
    {
        return $this->updateIban($ibanId, 0);
    }

    /**
     * Activate IBAN
     *
     * @param int $ibanId
     * @return array
     * @throws PratikOdemeException
     */
    public function activateIban(int $ibanId): array
    {
        return $this->updateIban($ibanId, 1);
    }

    /**
     * Get formatted balance information
     *
     * @return array
     * @throws PratikOdemeException
     */
    public function getFormattedBalance(): array
    {
        $response = $this->getBalance();
        $formatted = [];

        if (isset($response['walletInfo']) && is_array($response['walletInfo'])) {
            foreach ($response['walletInfo'] as $wallet) {
                $formatted[] = [
                    'walletId' => $wallet['walletId'],
                    'totalBalance' => $this->parseAmount($wallet['totalBalance'] ?? 0),
                    'unavailableBalance' => $this->parseAmount($wallet['unavailableBalance'] ?? 0),
                    'availableBalance' => $this->parseAmount(($wallet['totalBalance'] ?? 0) - ($wallet['unavailableBalance'] ?? 0)),
                    'iban' => $wallet['iban'] ?? '',
                    'currencyCode' => $wallet['currencyCode'] ?? 'TRY',
                ];
            }
        }

        return [
            'wallets' => $formatted,
            'transactionLimits' => $response['transactionLimits'] ?? [],
        ];
    }
}