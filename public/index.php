<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;

$app = new App();
$app->run();