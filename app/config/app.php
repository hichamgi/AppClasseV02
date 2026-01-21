<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2); // racine projet (AppClasseV02)
$dotenv = Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad(); // ne plante pas si .env absent

return [
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_name' => $_ENV['DB_NAME'] ?? 'appclassetest',
    'db_user' => $_ENV['DB_USER'] ?? 'root',
    'db_pass' => $_ENV['DB_PASS'] ?? '',
];
