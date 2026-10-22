<?php
// auth_server/token_exchange.php
session_start();

// 1. Устанавливаем заголовок JSON, так как этот шлюз общается только данными
header('Content-Type: application/json');

$config = require_once __DIR__ . '/app/config/config.php';

// 2. Принимаем POST-параметры от бэкенда магазина
$client_id     = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$code          = $_POST['code'] ?? '';
$redirect_uri  = $_POST['redirect_uri'] ?? '';

// 3. ПРОВЕРКА БЕЗОПАСНОСТИ №1: Аутентификация самого клиента (магазина)
if (!isset($config['allowed_clients'][$client_id])) {
    echo json_encode(['error' => 'invalid_client']);
    exit;
}

// Проверяем, совпадает ли секретная монета (пароль приложения)
if ($config['allowed_clients'][$client_id]['client_secret'] !== $client_secret) {
    echo json_encode(['error' => 'unauthorized_client_secret']);
    exit;
}

// 4. ПРОВЕРКА БЕЗОПАСНОСТИ №2: Валидация деревянного жетона (code)
// Ищем жетон в реестре сервера
if (!isset($_SESSION['oauth_codes'][$code])) {
    echo json_encode(['error' => 'invalid_grant', 'message' => 'code_not_found']);
    exit;
}

$saved_code_data = $_SESSION['oauth_codes'][$code];

// Проверяем, не истекли ли 30 секунд жизни жетона
if (time() > $saved_code_data['expires_at']) {
    unset($_SESSION['oauth_codes'][$code]); // Чистим протухший жетон
    echo json_encode(['error' => 'invalid_grant', 'message' => 'code_expired']);
    exit;
}

// Проверяем, что этот жетон был выдан именно этому клиенту и под этот же redirect_uri
if ($saved_code_data['client_id'] !== $client_id || $saved_code_data['redirect_uri'] !== $redirect_uri) {
    echo json_encode(['error' => 'invalid_grant', 'message' => 'context_mismatch']);
    exit;
}

// 5. ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ: Уничтожаем использованный жетон (одноразовое правило OAuth)
unset($_SESSION['oauth_codes'][$code]);

// 6. Чеканем чистое "золото" — Access Token
// В ванильном исполнении генерируем уникальную строковую монету
$access_token = 'gold_token_' . bin2hex(random_bytes(32));

// Запоминаем токен на сервере авторизации и привязываем к нему данные нашего Харона
if (!isset($_SESSION['oauth_tokens'])) {
    $_SESSION['oauth_tokens'] = [];
}

$_SESSION['oauth_tokens'][$access_token] = [
    'user_email' => $saved_code_data['user_email'],
    'user_name'  => $saved_code_data['user_name'],
    'user_role'  => $saved_code_data['user_role'],
    'expires_at' => time() + 3600 // Золото действует 1 час (3600 секунд)
];

// 7. Отдаем золото лодочнику в формате JSON
echo json_encode([
    'access_token' => $access_token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600
]);
exit;
