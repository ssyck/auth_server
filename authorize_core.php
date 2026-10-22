<?php
// auth_server/authorize_core.php
session_start();

// Подключаем конфигурацию ядра сервера авторизации
$config = require_once __DIR__ . '/app/config/config.php';

// 1. Принимаем входящие параметры от проводника магазина
$client_id     = $_GET['client_id'] ?? '';
$redirect_uri  = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';

// 2. Жесткая валидация клиента (проверка по белому списку)
if (!isset($config['allowed_clients'][$client_id])) {
    die("oauth_error: unauthorized_client_id");
}

if ($config['allowed_clients'][$client_id]['redirect_uri'] !== $redirect_uri) {
    die("oauth_error: redirect_uri_mismatch");
}

if ($response_type !== 'code') {
    die("oauth_error: unsupported_response_type");
}

// 3. Имитируем успешный вход пользователя (так как фронтенда с формой у нас нет)
// Представим, что в системе залогинился главный админ
$logged_in_user_email = 'charon@styx.ru';
$user_data = $config['users'][$logged_in_user_email];

// 4. Чеканим "деревянный жетон" (Authorization Code)
$temporary_code = bin2hex(random_bytes(16));

// ЗАПИСЫВАЕМ ЖЕТОН В РЕЕСТР СЕРВЕРА (в сессию auth_server)
// Мы связываем код с клиентом, пользователем и ставим метку времени (Timestamp)
if (!isset($_SESSION['oauth_codes'])) {
    $_SESSION['oauth_codes'] = [];
}

$_SESSION['oauth_codes'][$temporary_code] = [
    'client_id'    => $client_id,
    'redirect_uri' => $redirect_uri,
    'user_email'   => $logged_in_user_email,
    'user_name'    => $user_data['name'],
    'user_role'    => $user_data['role'],
    'expires_at'   => time() + $config['code_lifespan'] // жетон протухнет через 30 секунд
];

// 5. Отправляем лодку с жетоном обратно в магазин через Стикс (URL)
$redirect_back_url = $redirect_uri . "?code=" . $temporary_code;

header("Location: " . $redirect_back_url);
exit;
