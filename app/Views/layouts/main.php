<?php
use App\Core\Auth;
use App\Core\Csrf;

$baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
$baseUrl = rtrim($baseUrl, '/');

$user = Auth::user();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AppClasseV02</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app.css">
  <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
  <meta name="base-url" content="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= $baseUrl ?>/dashboard">AppClasseV02</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/classes">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/eleves">Élèves</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 text-white">
        <span class="small"><?= htmlspecialchars(($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-outline-light btn-sm" href="<?= $baseUrl ?>/logout">Déconnexion</a>
      </div>
    </div>
  </div>
</nav>

<main class="container py-4">
  <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/app.js"></script>
<div class="modal fade" id="appModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" id="appModalContent"></div>
  </div>
</div>
</body>
</html>