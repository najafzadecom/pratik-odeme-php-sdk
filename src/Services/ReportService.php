<?php

namespace PratikOdeme\Services;

use PratikOdeme\Exceptions\PratikOdemeException;
use DateTime;

/**
 * Report Service
 * 
 * Hesabat və siyahı əməliyyatları üçün servis
 */
class ReportService extends BaseService
{
    /**
     * Transaction status constants
     */
    const TRANSACTION_STATUS = [
        '0' => 'Bekleyen İşlemler',
        '1' => 'Tamamlanan İşlemler',
        '2' => 'Reddedilen İşlemler',
        '3' => 'İptal',
        '4' => 'Teknik İptal (fast iptali)',
        '5' => 'Gönderliyor',
        '6' => 'Blokeye Alınmış',
    ];

    /**
     * Transaction type constants
     */
    const TRANSACTION_TYPES = [
        '0' => 'Tüm işlemleri listele',
        '2' => 'Bireysel Bakiye Yükleme',
        '3' => 'Bireysel CashBack',
        '4' => 'Bireysel İade',
        '5' => 'Bireysel Para Çıkışı',
        '6' => 'Kurumsal Cüzdana Bakiye Yükleme',
        '7' => 'Kurumsal CashBack',
        '8' => 'Kurumsal İade',
        '9' => 'Kurumsal Cüzdandan Para Çıkışı',
    ];

    /**
     * Page size options
     */
    const PAGE_SIZES = ['top10', 'top25', 'top50', 'top100', 'all'];

    /**
     * Get money transaction history
     *
     * @param array $params
     * @return array
     * @throws PratikOdemeException
     */
    public function getMoneyTransactionHistory(array $params = []): array
    {
        // Default parameters
        $defaults = [
            'sDate' => date('d-m-Y', strtotime('-30 days')) . 'T00:00:00',
            'lDate' => date('d-m-Y') . 'T23:59:59',
            'transactionTypeId' => '0',
            'transactionStatus' => '1',
            'description' => '',
            'minAmount' => '0',
            'maxAmount' => '0',
            'currentPage' => '1',
            'qSize' => 'top100'
        ];

        $params = array_merge($defaults, $params);

        // Validate page size
        if (!in_array($params['qSize'], self::PAGE_SIZES)) {
            throw new PratikOdemeException('Invalid page size. Must be one of: ' . implode(', ', self::PAGE_SIZES));
        }

        // Validate transaction status
        if (!array_key_exists($params['transactionStatus'], self::TRANSACTION_STATUS) && $params['transactionStatus'] !== '') {
            throw new PratikOdemeException('Invalid transaction status');
        }

        // Validate transaction type
        if (!array_key_exists($params['transactionTypeId'], self::TRANSACTION_TYPES)) {
            throw new PratikOdemeException('Invalid transaction type');
        }

        $data = [
            'thisStep' => 'Money_Transaction_History',
            'sDate' => $params['sDate'],
            'lDate' => $params['lDate'],
            'transactionTypeId' => $params['transactionTypeId'],
            'transactionStatus' => $params['transactionStatus'],
            'description' => $params['description'],
            'minAmount' => $params['minAmount'],
            'maxAmount' => $params['maxAmount'],
            'currentPage' => $params['currentPage'],
            'qSize' => $params['qSize']
        ];

        // Add wallet ID if provided
        if (isset($params['walletId'])) {
            $data['walletId'] = $params['walletId'];
        }

        $response = $this->makeRequest('merchantapi/money-transaction-history', $data);

        // Parse amounts in transaction list
        if (isset($response['transactionList']) && is_array($response['transactionList'])) {
            foreach ($response['transactionList'] as &$transaction) {
                if (isset($transaction['transactionAmount'])) {
                    $transaction['transactionAmountFormatted'] = $this->parseAmount($transaction['transactionAmount']);
                }
                if (isset($transaction['transactionFeeAmount'])) {
                    $transaction['transactionFeeAmountFormatted'] = $this->parseAmount($transaction['transactionFeeAmount']);
                }
                if (isset($transaction['nextAmount'])) {
                    $transaction['nextAmountFormatted'] = $this->parseAmount($transaction['nextAmount']);
                }
            }
        }

        return $response;
    }

    /**
     * Get money transaction summary
     *
     * @param array $params
     * @return array
     * @throws PratikOdemeException
     */
    public function getMoneyTransactionSummary(array $params = []): array
    {
        // Default parameters
        $defaults = [
            'sDate' => date('d-m-Y', strtotime('-30 days')),
            'lDate' => date('d-m-Y'),
            'currentPage' => '1',
            'qSize' => 'top10'
        ];

        $params = array_merge($defaults, $params);

        // Validate page size
        if (!in_array($params['qSize'], self::PAGE_SIZES)) {
            throw new PratikOdemeException('Invalid page size. Must be one of: ' . implode(', ', self::PAGE_SIZES));
        }

        $data = [
            'thisStep' => 'Money_Transaction_Summary',
            'sDate' => $params['sDate'],
            'lDate' => $params['lDate'],
            'currentPage' => $params['currentPage'],
            'qSize' => $params['qSize']
        ];

        $response = $this->makeRequest('merchantapi/money-transaction-summary', $data);

        // Parse amounts in response
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as &$item) {
                if (isset($item['incomingTransactionAmount'])) {
                    $item['incomingTransactionAmountFormatted'] = $this->parseAmount($item['incomingTransactionAmount']);
                }
                if (isset($item['outgoingTransactionAmount'])) {
                    $item['outgoingTransactionAmountFormatted'] = $this->parseAmount($item['outgoingTransactionAmount']);
                }
                if (isset($item['endOfDayBalance'])) {
                    $item['endOfDayBalanceFormatted'] = $this->parseAmount($item['endOfDayBalance']);
                }
            }
        }

        return $response;
    }

    /**
     * Get transaction history with date range
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function getTransactionHistoryByDateRange(
        DateTime $startDate,
        DateTime $endDate,
        array $options = []
    ): array {
        $params = array_merge($options, [
            'sDate' => $startDate->format('d-m-Y') . 'T00:00:00',
            'lDate' => $endDate->format('d-m-Y') . 'T23:59:59',
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Get transaction history by amount range
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function getTransactionHistoryByAmountRange(
        float $minAmount,
        float $maxAmount,
        array $options = []
    ): array {
        $params = array_merge($options, [
            'minAmount' => (string)$this->formatAmount($minAmount),
            'maxAmount' => (string)$this->formatAmount($maxAmount),
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Search transactions by description
     *
     * @param string $description
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function searchTransactionsByDescription(string $description, array $options = []): array
    {
        $params = array_merge($options, [
            'description' => $description,
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Get blocked transactions
     *
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function getBlockedTransactions(array $options = []): array
    {
        $params = array_merge($options, [
            'transactionStatus' => '6',
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Get completed transactions
     *
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function getCompletedTransactions(array $options = []): array
    {
        $params = array_merge($options, [
            'transactionStatus' => '1',
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Get pending transactions
     *
     * @param array $options
     * @return array
     * @throws PratikOdemeException
     */
    public function getPendingTransactions(array $options = []): array
    {
        $params = array_merge($options, [
            'transactionStatus' => '0',
        ]);

        return $this->getMoneyTransactionHistory($params);
    }

    /**
     * Get transaction status name
     *
     * @param string $status
     * @return string
     */
    public function getTransactionStatusName(string $status): string
    {
        return self::TRANSACTION_STATUS[$status] ?? 'Unknown';
    }

    /**
     * Get transaction type name
     *
     * @param string $type
     * @return string
     */
    public function getTransactionTypeName(string $type): string
    {
        return self::TRANSACTION_TYPES[$type] ?? 'Unknown';
    }

    /**
     * Get all transaction statuses
     *
     * @return array
     */
    public function getTransactionStatuses(): array
    {
        return self::TRANSACTION_STATUS;
    }

    /**
     * Get all transaction types
     *
     * @return array
     */
    public function getTransactionTypes(): array
    {
        return self::TRANSACTION_TYPES;
    }
}