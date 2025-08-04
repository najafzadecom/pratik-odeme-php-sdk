<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PratikOdeme\PratikOdemeClient;
use PratikOdeme\Exceptions\PratikOdemeException;

// API məlumatları
$baseUrl = 'https://api.pratikode.com.tr';
$channelId = '20';
$username = '12221111111';
$password = 'your_password';
$dealerCode = 'PRTK94677';

try {
    // Client yaradın
    $client = new PratikOdemeClient($baseUrl, $channelId);
    
    echo "=== Pratik Ödeme PHP SDK Nümunəsi ===\n\n";
    
    // 1. Giriş
    echo "1. Giriş...\n";
    $loginResponse = $client->auth->login($username, $password, $dealerCode);
    echo "✓ Giriş uğurlu! Token alındı.\n\n";
    
    // 2. Secret key yaradın
    echo "2. Secret key yaradılır...\n";
    $secretResponse = $client->auth->createSecretKey();
    echo "✓ Secret key yaradıldı!\n\n";
    
    // 3. Hesab məlumatları
    echo "3. Hesab məlumatları alınır...\n";
    $accountInfo = $client->account->getAccountBasic();
    if (isset($accountInfo['data'][0])) {
        $account = $accountInfo['data'][0];
        echo "✓ Hesab sahibi: {$account['name']} {$account['lastName']}\n";
        echo "✓ Şirkət: {$account['userTitle']}\n";
        echo "✓ Email: {$account['email']}\n\n";
    }
    
    // 4. Balans yoxlayın
    echo "4. Balans yoxlanılır...\n";
    $balance = $client->account->getFormattedBalance();
    foreach ($balance['wallets'] as $wallet) {
        echo "✓ Cüzdan ID: {$wallet['walletId']}\n";
        echo "✓ IBAN: {$wallet['iban']}\n";
        echo "✓ Ümumi balans: {$wallet['totalBalance']} {$wallet['currencyCode']}\n";
        echo "✓ İstifadə edilə bilən: {$wallet['availableBalance']} {$wallet['currencyCode']}\n\n";
    }
    
    // 5. IBAN siyahısı
    echo "5. IBAN siyahısı alınır...\n";
    $ibanList = $client->account->getIbanList();
    if (isset($ibanList['ibanList'])) {
        echo "✓ Qeydiyyatlı IBAN sayı: " . count($ibanList['ibanList']) . "\n";
        foreach ($ibanList['ibanList'] as $iban) {
            echo "  - {$iban['iban']} ({$iban['accountName']})\n";
        }
        echo "\n";
    }
    
    // 6. Əməliyyat tarixçəsi (son 10 gün)
    echo "6. Son 10 günün əməliyyat tarixçəsi...\n";
    $history = $client->report->getMoneyTransactionHistory([
        'sDate' => date('d-m-Y', strtotime('-10 days')) . 'T00:00:00',
        'lDate' => date('d-m-Y') . 'T23:59:59',
        'qSize' => 'top10'
    ]);
    
    if (isset($history['transactionList'])) {
        echo "✓ Tapılan əməliyyat sayı: " . count($history['transactionList']) . "\n";
        foreach ($history['transactionList'] as $transaction) {
            echo "  - {$transaction['createdDate']}: ";
            echo "{$transaction['transactionAmountFormatted']} TL ";
            echo "({$transaction['transactionStatusName']})\n";
        }
        echo "\n";
    }
    
    // 7. Biznes cüzdan yoxlama nümunəsi
    echo "7. Biznes cüzdan yoxlama nümunəsi...\n";
    try {
        $walletCheck = $client->transaction->checkBusinessWallet('5990000001');
        if (isset($walletCheck['walletInfo'][0])) {
            $wallet = $walletCheck['walletInfo'][0];
            echo "✓ Cüzdan tapıldı: {$wallet['name']} {$wallet['lastName']}\n";
            echo "✓ Cüzdan ID: {$wallet['walletId']}\n\n";
        }
    } catch (PratikOdemeException $e) {
        echo "ℹ Nümunə cüzdan tapılmadı (bu normaldır): {$e->getMessage()}\n\n";
    }
    
    // 8. Ödəmə tipləri
    echo "8. Mövcud ödəmə tipləri:\n";
    $paymentTypes = $client->transaction->getPaymentTypes();
    foreach ($paymentTypes as $code => $name) {
        echo "  - {$code}: {$name}\n";
    }
    echo "\n";
    
    // 9. Əməliyyat statusları
    echo "9. Əməliyyat statusları:\n";
    $statuses = $client->report->getTransactionStatuses();
    foreach ($statuses as $code => $name) {
        echo "  - {$code}: {$name}\n";
    }
    echo "\n";
    
    echo "=== Bütün əməliyyatlar uğurla tamamlandı! ===\n";
    
} catch (PratikOdemeException $e) {
    echo "❌ API Xətası: " . $e->getMessage() . "\n";
    echo "Response Code: " . $e->getResponseCode() . "\n";
    
    if ($e->getResponseData()) {
        echo "Response Data:\n";
        print_r($e->getResponseData());
    }
} catch (Exception $e) {
    echo "❌ Ümumi xəta: " . $e->getMessage() . "\n";
}