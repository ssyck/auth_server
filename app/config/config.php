<?php
// auth_server/app/config/config.php

return [
    // Белый список доверенных приложений-клиентов
    'allowed_clients' => [
        'my_shop_admin' => [
            'client_secret' => 'gold_coin_secret_999_xyz', // Должен строго совпадать с конфигом магазина
            'redirect_uri'  => 'http://my_shop.local/admin.php'
        ]
    ],
 
    // Вместо базы данных пользователей — список сотрудников и их ролей
    'users' => [
        'charon@styx.ru' => [
            'name' => 'Харон Лодочник',
            'role' => 'admin'
        ],
        'manager@shop.ru' => [
            'name' => 'Алексей Менеджер',
            'role' => 'manager'
        ]
    ],
    
    // Срок действия временного деревянного жетона (code) в секундах
    'code_lifespan' => 30, 
];
