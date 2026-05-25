<?php
/**
 * Bonus Client - Tek dosya, saf PHP, composer yok.
 *
 * Kurulum:
 *   1. THIS_URL'i bu dosyanın dışarıdan erişilebilir URL'iyle değiştir.
 *   2. LARAVEL_API'yi Laravel projenin API adresine göre ayarla.
 *   3. STORAGE_DIR dizini web'den erişilemez bir yerde olmalı (tercihen webroot dışı).
 */

define('LARAVEL_API', 'http://127.0.0.1:8000/api');
define('THIS_URL',    'http://127.0.0.1:8001/'); // dışarıdan erişilebilir adres
define('STORAGE_DIR', __DIR__ . '/bonus_callbacks/');      // webroot dışına taşı

// --- Router ---
$action = $_GET['action'] ?? '';

if ($action === 'callback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleCallback();
    exit;
}

if ($action === 'status' && isset($_GET['uuid'])) {
    handleStatus($_GET['uuid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleFormSubmit();
    exit;
}

showForm();

// -----------------------------------------------------------------------------

function handleCallback(): void
{
    $body = json_decode(file_get_contents('php://input'), true);

    $uuid   = $body['uuid']            ?? null;
    $secret = $body['callback_secret'] ?? null;

    if (!$uuid || !$secret) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'reason' => 'uuid veya secret eksik']);
        return;
    }

    storageInit();

    $secretFile = STORAGE_DIR . hash('sha256', $uuid) . '.secret';

    if (!file_exists($secretFile)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'reason' => 'Bilinmeyen uuid']);
        return;
    }

    $storedSecret = trim(file_get_contents($secretFile));

    if (!hash_equals($storedSecret, $secret)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'reason' => 'Geçersiz secret']);
        return;
    }

    // Secret eşleşti, sonucu kaydet
    $dataFile = STORAGE_DIR . hash('sha256', $uuid) . '.json';
    file_put_contents($dataFile, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    http_response_code(200);
    echo json_encode(['ok' => true]);
}

function handleStatus(string $uuid): void
{
    header('Content-Type: application/json');
    storageInit();

    $dataFile = STORAGE_DIR . hash('sha256', $uuid) . '.json';

    if (!file_exists($dataFile)) {
        echo json_encode(['status' => 'pending']);
        return;
    }

    echo file_get_contents($dataFile);
}

function handleFormSubmit(): void
{
    $username = trim($_POST['username'] ?? '');
    $userId   = trim($_POST['user_id']  ?? '');
    $bonus    = trim($_POST['bonus']    ?? '');

    if (empty($bonus) || (empty($username) && empty($userId))) {
        showForm('Kullanıcı adı/ID ve bonus alanları zorunlu.');
        return;
    }

    // Her istek için benzersiz secret üret
    $secret      = bin2hex(random_bytes(32));
    $callbackUrl = THIS_URL . '?action=callback';

    $payload = array_filter([
        'username'        => $username ?: null,
        'user_id'         => $userId   ?: null,
        'bonus'           => $bonus,
        'callback_url'    => $callbackUrl,
        'callback_secret' => $secret,
    ]);

    $ch = curl_init(LARAVEL_API . '/bonus/request');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($raw, true);

    if ($httpCode !== 200 || !($response['success'] ?? false)) {
        $msg = $response['message'] ?? "HTTP {$httpCode}";
        showForm("API Hatası: {$msg}");
        return;
    }

    $uuid = $response['bonusRequest']['uuid'];

    // Secret'i sakla
    storageInit();
    file_put_contents(STORAGE_DIR . hash('sha256', $uuid) . '.secret', $secret);

    showWaiting($uuid);
}

// -----------------------------------------------------------------------------

function storageInit(): void
{
    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0700, true);
        // Dizini web'den koru
        file_put_contents(STORAGE_DIR . '.htaccess', "Deny from all\n");
    }
}

function showForm(string $error = ''): void
{
    $errorHtml = $error
        ? '<div style="background:#fee;border:1px solid #c00;padding:10px;margin-bottom:16px;border-radius:4px;">' . htmlspecialchars($error) . '</div>'
        : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bonus İste</title>
    <style>
        body { font-family: sans-serif; max-width: 480px; margin: 60px auto; padding: 0 16px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 14px; }
        input { width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 14px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <h2>Bonus Talebi</h2>
    {$errorHtml}
    <form method="POST">
        <label>Kullanıcı Adı</label>
        <input type="text" value="palu" name="username" placeholder="örn: ahmet123">
        <label>Kullanıcı ID</label>
        <input type="text" name="user_id" placeholder="ya da ID ile gir">
        <label>Bonus UUID</label>
        <input type="text" value="527d745f-269a-4e88-9677-3943a057093e" name="bonus" required placeholder="bonus uuid">
        <button type="submit">Talep Gönder</button>
    </form>
</body>
</html>
HTML;
}

function showWaiting(string $uuid): void
{
    $safeUuid = htmlspecialchars($uuid);
    $jsBase   = json_encode(THIS_URL . '?action=status&uuid=');
    $jsUuid   = json_encode($uuid);
    echo <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşleniyor...</title>
    <style>
        body { font-family: sans-serif; max-width: 520px; margin: 60px auto; padding: 0 16px; text-align: center; }
        #result { margin-top: 24px; padding: 16px; border-radius: 6px; text-align: left; white-space: pre-wrap; font-size: 14px; display: none; }
        .ok  { background: #f0fdf4; border: 1px solid #16a34a; }
        .err { background: #fef2f2; border: 1px solid #dc2626; }
        #spinner { font-size: 32px; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="spinner">⟳</div>
    <h3>Bonus işleniyor, lütfen bekleyin...</h3>
    <small>UUID: {$safeUuid}</small>
    <div id="result"></div>

    <script>
        const statusUrl = {$jsBase} + {$jsUuid};
        const interval  = setInterval(async () => {
            try {
                const res  = await fetch(statusUrl);
                const data = await res.json();

                if (data.status === 'pending') return;

                clearInterval(interval);
                document.getElementById('spinner').style.display = 'none';

                const box = document.getElementById('result');
                box.style.display = 'block';
                box.className     = data.status === 'approved_assigned' ? 'result ok' : 'result err';
                box.textContent   = JSON.stringify(data, null, 2);
            } catch (e) {
                // ağ hatası, devam et
            }
        }, 2000);
    </script>
</body>
</html>
HTML;
}
