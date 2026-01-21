<?php $baseUrl = rtrim((require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '', '/'); ?>

<h1 class="h3 mb-3">Élèves</h1>

<form class="row g-2 mb-3" method="get" action="<?= $baseUrl ?>/eleves">
  <div class="col-md-6">
    <input class="form-control" name="q" value="<?= htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Recherche: nom, prénom, SGS...">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Rechercher</button>
  </div>
  <div class="col-md-2">
    <a class="btn btn-outline-secondary w-100" href="<?= $baseUrl ?>/eleves">Reset</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>SGS</th>
          <th>Nom</th>
          <th>Prénom</th>
          <th>Sexe</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (($eleves ?? []) as $e): ?>
        <tr>
          <td><?= (int)$e['id'] ?></td>
          <td><?= htmlspecialchars($e['numerosgs'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($e['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($e['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($e['sexe'] ?? 'M', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= $baseUrl ?>/eleves/<?= (int)$e['id'] ?>">Ouvrir</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>