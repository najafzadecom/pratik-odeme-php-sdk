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
    // Client yaradın və giriş edin
    $client = new PratikOdemeClient($baseUrl, $channelId);
    $client->auth->login($username, $password, $dealerCode);
    $client->auth->createSecretKey();
    
    echo "=== Əməliyyat Nümunələri ===\n\n";
    
    // Balansı yoxlayın
    $balance = $client->account->getFormattedBalance();
    $senderWalletId = $balance['wallets'][0]['walletId'] ?? null;
    
    if (!$senderWalletId) {
        throw new Exception('Cüzdan ID tapılmadı');
    }
    
    echo "İstifadə ediləcək cüzdan ID: {$senderWalletId}\n";
    echo "Mövcud balans: {$balance['wallets'][0]['availableBalance']} TL\n\n";
    
    // 1. Bankdan cüzdana pul yükləmə nümunəsi
    echo "1. Bankdan cüzdana pul yükləmə...\n";
    try {
        $topupResult = $client->transaction->topupBankToBusinessWallet([
            'topupBusinessWalletMethod' => 'publicIBAN',
            'receiverIban' => 'TR112000000000000000000001',
            'senderIban' => 'TR112000000000000000000777',
            'senderAccountHolderName' => 'Test Göndərən',
            'senderDescription' => 'Test yükləmə əməliyyatı',
            'amount' => 5.00, // 5 TL
            'currencyCode' => 'TRY',
            'description' => 'PHP SDK test əməliyyatı',
            'extTransactionId' => 'PHP_SDK_' . time()
        ]);
        
        echo "✓ Yükləmə sorğusu göndərildi!\n";
        echo "Transaction ID: {$topupResult['transactionId']}\n\n";
        
    } catch (PratikOdemeException $e) {
        echo "ℹ Yükləmə xətası (test ortamında normaldır): {$e->getMessage()}\n\n";
    }
    
    // 2. Cüzdandan banka pul göndərmə nümunəsi
    echo "2. Cüzdandan banka pul göndərmə...\n";
    try {
        $sendResult = $client->transaction->sendMoneyToBank([
            'senderWalletId' => $senderWalletId,
            'receiverAccountHolderName' => 'Test Alıcı',
            'receiverIban' => 'TR280013400001493472700178',
            'receiverNationalIdOrTaxNo' => '06220000622',
            'senderDescription' => 'Test bank köçürməsi',
            'paymentType' => '99', // Diğer
            'amount' => 1.00, // 1 TL
            'currencyCode' => 'TRY',
            'extTransactionId' => 'BANK_' . time(),
            'confirmType' => 'SMS'
        ]);
        
        echo "✓ Göndərmə sorğusu hazırlandı!\n";
        
        if (isset($sendResult['NextStep']) && $sendResult['NextStep'] === 'Approve_Send_Money') {
            echo "✓ SMS kodu göndərildi (test ortamında header-də gəlir)\n";
            
            $transactionDetails = $sendResult['transactionDetails'][0];
            echo "Transaction ID: {$transactionDetails['transactionId']}\n";
            echo "Məbləğ: " . ($transactionDetails['transactionAmount'] / 100) . " TL\n";
            
            // Test ortamında SMS kodu header-də gəlir
            if (isset($sendResult['_headers']['smscode'])) {
                $smsCode = $sendResult['_headers']['smscode'];
                echo "SMS Kodu: {$smsCode}\n";
                
                // Əməliyyatı təsdiq edin
                echo "\n3. Əməliyyatı təsdiq etmə...\n";
                $approveResult = $client->transaction->approveSendMoney(
                    senderWalletId: $transactionDetails['senderWalletId'],
                    amount: 1.00,
                    transactionId: $transactionDetails['transactionId'],
                    passCode: $smsCode
                );
                
                echo "✓ Əməliyyat təsdiqləndi!\n";
                echo "Yeni balans: " . ($approveResult['transactionDetails'][0]['balanceAvailable'] / 100) . " TL\n\n";
            }
        }
        
    } catch (PratikOdemeException $e) {
        echo "ℹ Göndərmə xətası: {$e->getMessage()}\n\n";
    }
    
    // 3. Cüzdandan cüzdana köçürmə nümunəsi
    echo "4. Cüzdandan cüzdana köçürmə nümunəsi...\n";
    try {
        // Əvvəlcə başqa cüzdan yoxlayaq
        $targetWallet = $client->transaction->checkBusinessWallet('5990000001');
        
        if (isset($targetWallet['walletInfo'][0])) {
            $receiverWalletId = $targetWallet['walletInfo'][0]['walletId'];
            
            $walletResult = $client->transaction->sendMoneyToWallet([
                'senderWalletId' => $senderWalletId,
                'receiverWalletId' => $receiverWalletId,
                'senderDescription' => 'Cüzdandan cüzdana test',
                'paymentType' => '04', // E-Ticaret
                'amount' => 0.50, // 50 qəpik
                'currencyCode' => 'TRY',
                'extTransactionId' => 'WALLET_' . time(),
                'confirmType' => 'SMS'
            ]);
            
            echo "✓ Cüzdan köçürməsi hazırlandı!\n";
            if (isset($walletResult['transactionDetails'][0])) {
                echo "Transaction ID: {$walletResult['transactionDetails'][0]['transactionId']}\n";
            }
        }
        
    } catch (PratikOdemeException $e) {
        echo "ℹ Cüzdan köçürməsi xətası: {$e->getMessage()}\n\n";
    }
    
    // 4. Əməliyyat statusu yoxlama
    echo "5. Əməliyyat statusu yoxlama...\n";
    try {
        // Son əməliyyatları alın
        $history = $client->report->getMoneyTransactionHistory([
            'qSize' => 'top5',
            'transactionStatus' => '' // Bütün statuslar
        ]);
        
        if (isset($history['transactionList'][0])) {
            $lastTransaction = $history['transactionList'][0];
            
            $status = $client->transaction->getTransactionStatus(
                transactionId: $lastTransaction['transactionId']
            );
            
            echo "✓ Son əməliyyat statusu yoxlandı:\n";
            echo "Transaction ID: {$status['transactionId']}\n";
            echo "Status: {$status['transactionStatusName']}\n";
            echo "Tarix: {$status['createdDate']}\n\n";
        }
        
    } catch (PratikOdemeException $e) {
        echo "ℹ Status yoxlama xətası: {$e->getMessage()}\n\n";
    }
    
    // 5. Bloklanmış əməliyyatları yoxlayın
    echo "6. Bloklanmış əməliyyatlar...\n";
    $blockedTransactions = $client->report->getBlockedTransactions();
    
    if (isset($blockedTransactions['transactionList']) && count($blockedTransactions['transactionList']) > 0) {
        echo "✓ Bloklanmış əməliyyat sayı: " . count($blockedTransactions['transactionList']) . "\n";
        foreach ($blockedTransactions['transactionList'] as $blocked) {
            echo "  - {$blocked['transactionId']}: {$blocked['blockedDetail']}\n";
        }
    } else {
        echo "✓ Bloklanmış əməliyyat yoxdur\n";
    }
    echo "\n";
    
    echo "=== Əməliyyat nümunələri tamamlandı! ===\n";
    
} catch (PratikOdemeException $e) {
    echo "❌ API Xətası: " . $e->getMessage() . "\n";
    echo "Response Code: " . $e->getResponseCode() . "\n";
} catch (Exception $e) {
    echo "❌ Ümumi xəta: " . $e->getMessage() . "\n";
}