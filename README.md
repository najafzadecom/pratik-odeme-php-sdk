# Pratik Ödeme PHP SDK

Pratik Ödeme Kurumsal Cüzdan API-si üçün PHP kütüphanəsi.

## Quraşdırma

Composer vasitəsilə quraşdırın:

```bash
composer require pratikodeme/php-sdk
```

## İstifadə

### Əsas quraşdırma

```php
<?php

require_once 'vendor/autoload.php';

use PratikOdeme\PratikOdemeClient;
use PratikOdeme\Exceptions\PratikOdemeException;

// Client yaradın
$client = new PratikOdemeClient('https://api.pratikode.com.tr', '20');
```

### Giriş

```php
try {
    // Giriş edin
    $response = $client->auth->login(
        username: '12221111111',
        password: 'your_password',
        dealerCode: 'PRTK94677',
        branchCode: '0' // opsiyonel
    );
    
    echo "Giriş uğurlu! Token: " . $response['Token'];
    
    // Secret key yaradın
    $secretResponse = $client->auth->createSecretKey();
    echo "Secret key yaradıldı!";
    
} catch (PratikOdemeException $e) {
    echo "Xəta: " . $e->getMessage();
    echo "Response Code: " . $e->getResponseCode();
}
```

### Hesab məlumatları

```php
try {
    // Hesab məlumatlarını alın
    $accountInfo = $client->account->getAccountBasic();
    print_r($accountInfo);
    
    // Balansı yoxlayın
    $balance = $client->account->getFormattedBalance();
    foreach ($balance['wallets'] as $wallet) {
        echo "Cüzdan ID: " . $wallet['walletId'] . "\n";
        echo "Ümumi balans: " . $wallet['totalBalance'] . " TL\n";
        echo "İstifadə edilə bilən: " . $wallet['availableBalance'] . " TL\n";
    }
    
    // IBAN əlavə edin
    $client->account->saveIban('TR280013400001493472700178', 'Hesab Sahibi Adı');
    
    // IBAN siyahısını alın
    $ibanList = $client->account->getIbanList();
    print_r($ibanList);
    
} catch (PratikOdemeException $e) {
    echo "Xəta: " . $e->getMessage();
}
```

### Əməliyyatlar

```php
try {
    // Biznes cüzdan yoxlayın
    $walletCheck = $client->transaction->checkBusinessWallet('5990000001');
    print_r($walletCheck);
    
    // Bankdan cüzdana pul yükləyin
    $topupResult = $client->transaction->topupBankToBusinessWallet([
        'topupBusinessWalletMethod' => 'publicIBAN',
        'receiverIban' => 'TR112000000000000000000001',
        'senderIban' => 'TR112000000000000000000777',
        'senderAccountHolderName' => 'Göndərən Adı',
        'senderDescription' => 'Açıqlama',
        'amount' => 5.00, // TL
        'currencyCode' => 'TRY',
        'description' => 'Test əməliyyatı',
        'extTransactionId' => 'UNIQUE_ID_' . time()
    ]);
    
    // Cüzdandan banka pul göndərin
    $sendResult = $client->transaction->sendMoneyToBank([
        'senderWalletId' => 'WALLET_ID',
        'receiverAccountHolderName' => 'Alıcı Adı',
        'receiverIban' => 'TR280013400001493472700178',
        'receiverNationalIdOrTaxNo' => '06220000622',
        'senderDescription' => 'Banka köçürməsi',
        'paymentType' => '99', // Diğer
        'amount' => 5.00, // TL
        'currencyCode' => 'TRY',
        'extTransactionId' => 'UNIQUE_ID_' . time(),
        'confirmType' => 'SMS' // və ya 'confirmKey'
    ]);
    
    // Əməliyyatı təsdiq edin
    if (isset($sendResult['NextStep']) && $sendResult['NextStep'] === 'Approve_Send_Money') {
        $transactionDetails = $sendResult['transactionDetails'][0];
        $smsCode = $sendResult['_headers']['smscode'] ?? 'SMS_CODE_FROM_PHONE';
        
        $approveResult = $client->transaction->approveSendMoney(
            senderWalletId: $transactionDetails['senderWalletId'],
            amount: 5.00,
            transactionId: $transactionDetails['transactionId'],
            passCode: $smsCode
        );
        
        echo "Əməliyyat təsdiqləndi!";
    }
    
} catch (PratikOdemeException $e) {
    echo "Xəta: " . $e->getMessage();
}
```

### Hesabatlar

```php
try {
    // Əməliyyat tarixçəsini alın
    $history = $client->report->getMoneyTransactionHistory([
        'sDate' => '01-01-2024T00:00:00',
        'lDate' => '31-12-2024T23:59:59',
        'transactionTypeId' => '0', // Bütün əməliyyatlar
        'transactionStatus' => '1', // Tamamlanan
        'currentPage' => '1',
        'qSize' => 'top100'
    ]);
    
    foreach ($history['transactionList'] as $transaction) {
        echo "Əməliyyat ID: " . $transaction['transactionId'] . "\n";
        echo "Məbləğ: " . $transaction['transactionAmountFormatted'] . " TL\n";
        echo "Tarix: " . $transaction['createdDate'] . "\n";
        echo "Status: " . $transaction['transactionStatusName'] . "\n\n";
    }
    
    // Əməliyyat xülasəsi
    $summary = $client->report->getMoneyTransactionSummary([
        'sDate' => '01-01-2024',
        'lDate' => '31-12-2024'
    ]);
    
    // Bloklanmış əməliyyatları alın
    $blockedTransactions = $client->report->getBlockedTransactions();
    
    // Təsdiq gözləyən əməliyyatlar
    $pendingTransactions = $client->report->getPendingTransactions();
    
} catch (PratikOdemeException $e) {
    echo "Xəta: " . $e->getMessage();
}
```

### Xəta idarəçiliyi

```php
try {
    // API əməliyyatı
    $result = $client->account->getBalance();
} catch (PratikOdemeException $e) {
    echo "API Xətası: " . $e->getMessage() . "\n";
    echo "Response Code: " . $e->getResponseCode() . "\n";
    
    // Tam response məlumatı
    $responseData = $e->getResponseData();
    if ($responseData) {
        print_r($responseData);
    }
}
```

## API Endpoint-ləri

### Authentication
- `login()` - Giriş
- `createSecretKey()` - Secret key yaratma
- `createConfirmKey()` - Confirm key yaratma
- `getErrorCodeList()` - Xəta kodları siyahısı

### Account Management
- `getAccountBasic()` - Hesab məlumatları
- `getBalance()` - Balans sorğusu
- `getFormattedBalance()` - Formatlanmış balans
- `saveIban()` - IBAN əlavə etmə
- `updateIban()` - IBAN yeniləmə
- `deleteIban()` - IBAN silmə
- `getIbanList()` - IBAN siyahısı

### Transactions
- `checkBusinessWallet()` - Biznes cüzdan yoxlama
- `topupBankToBusinessWallet()` - Bankdan cüzdana yükləmə
- `sendMoneyToBank()` - Cüzdandan banka göndərmə
- `sendMoneyToWallet()` - Cüzdandan cüzdana göndərmə
- `approveSendMoney()` - Əməliyyat təsdiqi
- `getTransactionStatus()` - Əməliyyat statusu

### Reports
- `getMoneyTransactionHistory()` - Əməliyyat tarixçəsi
- `getMoneyTransactionSummary()` - Əməliyyat xülasəsi
- `getTransactionHistoryByDateRange()` - Tarix aralığına görə
- `getTransactionHistoryByAmountRange()` - Məbləğ aralığına görə
- `searchTransactionsByDescription()` - Açıqlamaya görə axtarış
- `getBlockedTransactions()` - Bloklanmış əməliyyatlar
- `getCompletedTransactions()` - Tamamlanmış əməliyyatlar
- `getPendingTransactions()` - Gözləyən əməliyyatlar

## Sabitlər

### Ödəmə Tipləri
```php
$paymentTypes = $client->transaction->getPaymentTypes();
// '99' => 'Diğer'
// '01' => 'Konut Kirası'
// '04' => 'E-Ticaret Ödemesi'
// və s.
```

### Əməliyyat Statusları
```php
$statuses = $client->report->getTransactionStatuses();
// '0' => 'Bekleyen İşlemler'
// '1' => 'Tamamlanan İşlemler'
// '6' => 'Blokeye Alınmış'
// və s.
```

## Test

```bash
composer test
```

## Kod keyfiyyəti

```bash
composer phpstan
composer phpcs
```

## Lisenziya

MIT

## Dəstək

API sənədləri və dəstək üçün [Pratik Ödeme](https://pratikode.com.tr) saytına müraciət edin.