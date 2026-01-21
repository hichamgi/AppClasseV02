<?php
return [
    'session_key' => 'user_id',
    'admin_usernames' => ['admin'], // adapte si besoin
    'two_factor' => [
        'enabled' => false,          // true si tu veux forcer l'étape 2FA
        'code_ttl_seconds' => 300,   // 5 min
    ],
];