<?php
    $baseUrl = (require dirname(__DIR__, 3) . '/config/app.php')['base_url'] ?? '';
    $baseUrl = rtrim($baseUrl, '/');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="d-flex align-items-center gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= $baseUrl ?>/admin/tools">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h3 class="m-0"><?= $this->e($tools[$key]['title'] ?? $key) ?></h3>
  </div>
</div>

<?php if (!empty($ok)): ?>
  <div class="alert alert-success py-2">Enregistré.</div>
<?php endif; ?>

<?php if (!empty($err)): ?>
  <div class="alert alert-danger py-2">Erreur: <?= $this->e($err) ?></div>
<?php endif; ?>

<?php
$data = $payload ?? [];
$partial = __DIR__ . '/partials/' . $key . '.php';
if (!file_exists($partial)) {
    echo '<div class="alert alert-warning">Partial not found: ' . $this->e($key) . '</div>';
} else {
    require $partial;
}
?>
