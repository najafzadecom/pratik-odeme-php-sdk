<?php

namespace PratikOdeme\Services;

use PratikOdeme\Exceptions\PratikOdemeException;

/**
 * Transaction Service
 * 
 * Əməliyyat (transaction) əməliyyatları üçün servis
 */
class TransactionService extends BaseService
{
    /**
     * Payment types
     */
    const PAYMENT_TYPES = [
        '99' => 'Diğer',
        '01' => 'Konut Kirası',
        '02' => 'İşyeri Kirası',
        '03' => 'Diğer Kiralar',
        '04' => 'E-Ticaret Ödemesi',
        '05' => 'Çalışan Ödemesi',
        '06' => 'Ticari Ödeme',
        '07' => 'Bireysel Ödeme',
        '08' => 'Ticari Finansal Yatırım',
        '09' => 'Bireysel Finansal Yatırım',
        '10' => 'Eğitim Ödemesi',
        '11' => 'Aidat Ödemesi',
    ];

    /**
     * Check business wallet
     *
     * @param string $dataInfo Phone number or wallet ID
     * @param string $currencyCode
     * @return array
     * @throws PratikOdemeException
     */
    public function checkBusinessWallet(string $dataInfo, string $currencyCode = 'TRY'): array
    {
        $this->validateRequired([
            'dataInfo' => $dataInfo,
            'currencyCode' => $currencyCode,
        ], ['dataInfo', 'currencyCode']);

        $data = [
            'dataInfo' => $dataInfo,
            'currencyCode' => $currencyCode
        ];

        return $this->makeRequest('merchantapi/check_business_wallet', $data);
    }

    /**
     * Topup from bank to business wallet
     *
     * @param array $params
     * @return array
     * @throws PratikOdemeException
     */
    public function topupBankToBusinessWallet(array $params): array
    {
        $required = [
            'topupBusinessWalletMethod',
            'receiverIban',
            'senderIban',
            'senderAccountHolderName',
            'amount',
            'currencyCode',
            'extTransactionId'
        ];

        $this->validateRequired($params, $required);

        // Format amount
        $amount = $this->formatAmount($params['amount']);

        // Generate hash key
        $hashData = [
            $amount,
            $params['receiverIban'],
            $params['extTransactionId']
        ];
        $hashKey = $this->client->generateHashKey($hashData);

        $data = [
            'thisStep' => 'Topup_Bank_to_Business_Wallet',
            'topupBusinessWalletMethod' => $params['topupBusinessWalletMethod'],
            'receiverIban' => $params['receiverIban'],
            'senderIban' => $params['senderIban'],
            'senderAccountHolderName' => $params['senderAccountHolderName'],
            'senderDescription' => $params['senderDescription'] ?? '',
            'amount' => $amount,
            'currencyCode' => $params['currencyCode'],
            'description' => $params['description'] ?? '',
            'extTransactionId' => $params['extTransactionId'],
            'hashKey' => $hashKey
        ];

        return $this->makeRequest('merchantapi/topup_bank_to_business_wallet', $data);
    }

    /**
     * Send money to bank
     *
     * @param array $params
     * @return array
     * @throws PratikOdemeException
     */
    public function sendMoneyToBank(array $params): array
    {
        $required = [
            'senderWalletId',
            'receiverAccountHolderName',
            'receiverIban',
            'receiverNationalIdOrTaxNo',
            'amount',
            'extTransactionId'
        ];

        $this->validateRequired($params, $required);

        // Format amount
        $amount = $this->formatAmount($params['amount']);

        // Generate hash key
        $hashData = [
            $params['senderWalletId'],
            $amount,
            $params['receiverIban'],
            $params['extTransactionId']
        ];
        $hashKey = $this->client->generateHashKey($hashData);

        $data = [
            'thisStep' => 'Send_Money_To_Bank',
            'senderWalletId' => $params['senderWalletId'],
            'receiverAccountHolderName' => $params['receiverAccountHolderName'],
            'receiverIban' => $params['receiverIban'],
            'receiverNationalIdOrTaxNo' => $params['receiverNationalIdOrTaxNo'],
            'senderDescription' => $params['senderDescription'] ?? '',
            'paymentType' => $params['paymentType'] ?? '99',
            'amount' => $amount,
            'currencyCode' => $params['currencyCode'] ?? 'TRY',
            'extTransactionId' => $params['extTransactionId'],
            'confirmType' => $params['confirmType'] ?? 'SMS',
            'hashKey' => $hashKey
        ];

        return $this->makeRequest('merchantapi/send_money_to_bank', $data);
    }

    /**
     * Send money to wallet
     *
     * @param array $params
     * @return array
     * @throws PratikOdemeException
     */
    public function sendMoneyToWallet(array $params): array
    {
        $required = [
            'senderWalletId',
            'receiverWalletId',
            'amount',
            'extTransactionId'
        ];

        $this->validateRequired($params, $required);

        // Format amount
        $amount = $this->formatAmount($params['amount']);

        // Generate hash key
        $hashData = [
            $params['senderWalletId'],
            $amount,
            $params['receiverWalletId'],
            $params['extTransactionId']
        ];
        $hashKey = $this->client->generateHashKey($hashData);

        $data = [
            'thisStep' => 'Send_Money_To_Wallet',
            'senderWalletId' => $params['senderWalletId'],
            'receiverWalletId' => $params['receiverWalletId'],
            'senderDescription' => $params['senderDescription'] ?? '',
            'paymentType' => $params['paymentType'] ?? '04',
            'amount' => $amount,
            'currencyCode' => $params['currencyCode'] ?? 'TRY',
            'extTransactionId' => $params['extTransactionId'],
            'confirmType' => $params['confirmType'] ?? 'SMS',
            'hashKey' => $hashKey
        ];

        return $this->makeRequest('merchantapi/send_money_to_wallet', $data);
    }

    /**
     * Approve send money transaction
     *
     * @param string $senderWalletId
     * @param float $amount
     * @param string $transactionId
     * @param string $passCode
     * @param string $currencyCode
     * @return array
     * @throws PratikOdemeException
     */
    public function approveSendMoney(
        string $senderWalletId,
        float $amount,
        string $transactionId,
        string $passCode,
        string $currencyCode = 'TRY'
    ): array {
        $this->validateRequired([
            'senderWalletId' => $senderWalletId,
            'amount' => $amount,
            'transactionId' => $transactionId,
            'passCode' => $passCode,
        ], ['senderWalletId', 'amount', 'transactionId', 'passCode']);

        $data = [
            'thisStep' => 'Approve_Send_Money',
            'senderWalletId' => $senderWalletId,
            'amount' => (string)$this->formatAmount($amount),
            'currencyCode' => $currencyCode,
            'transactionId' => $transactionId,
            'passCode' => $passCode
        ];

        return $this->makeRequest('merchantapi/send_money_confirm', $data);
    }

    /**
     * Get transaction status
     *
     * @param string|null $transactionId
     * @param string|null $extTransactionId
     * @param string $transactionTypeId
     * @return array
     * @throws PratikOdemeException
     */
    public function getTransactionStatus(
        ?string $transactionId = null,
        ?string $extTransactionId = null,
        string $transactionTypeId = '9'
    ): array {
        if (!$transactionId && !$extTransactionId) {
            throw new PratikOdemeException('Either transactionId or extTransactionId must be provided');
        }

        $data = [
            'transactionId' => $transactionId ?? '0',
            'extTransactionId' => $extTransactionId ?? '0',
            'transactionTypeId' => $transactionTypeId
        ];

        return $this->makeRequest('merchantapi/send_money_status', $data);
    }

    /**
     * Validate payment type
     *
     * @param string $paymentType
     * @return bool
     */
    public function isValidPaymentType(string $paymentType): bool
    {
        return array_key_exists($paymentType, self::PAYMENT_TYPES);
    }

    /**
     * Get payment type name
     *
     * @param string $paymentType
     * @return string
     */
    public function getPaymentTypeName(string $paymentType): string
    {
        return self::PAYMENT_TYPES[$paymentType] ?? 'Unknown';
    }

    /**
     * Get all payment types
     *
     * @return array
     */
    public function getPaymentTypes(): array
    {
        return self::PAYMENT_TYPES;
    }
}