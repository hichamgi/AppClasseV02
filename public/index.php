<?php
declare(strict_types=1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',              // important
  'domain' => '',             // laisse vide en LAN
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',         // 'Lax' recommandé pour login classique
]);

session_start();

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Core\App;

$app = new App();
$app->run();