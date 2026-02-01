<?php
$annee = $data['annee'] ?? null;
$classes = $data['classes'] ?? [];
?>

<?php if (!$annee): ?>
  <div class="alert alert-warning">Aucune année scolaire active.</div>
<?php else: ?>
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="post" action="<?= $baseUrl ?>/admin/tools/classes" class="row g-2">
        <?= $this->csrfField() ?>
        <div class="col-md-6">
          <input class="form-control" name="new_classe" placeholder="Nouvelle classe (ex: TCT1)">
        </div>
        <div class="col-md-3">
          <button class="btn btn-success w-100" type="submit"><i class="bi bi-plus"></i> Ajouter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?= $baseUrl ?>/admin/tools/classes">
        <?= $this->csrfField() ?>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:90px">ID</th>
                <th>Classe</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classes as $c): ?>
                <tr>
                  <td class="text-muted"><?= (int)$c['id'] ?></td>
                  <td>
                    <input class="form-control form-control-sm" name="classe[<?= (int)$c['id'] ?>]" value="<?= $this->e($c['classe'] ?? '') ?>">
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <button class="btn btn-primary" type="submit">
          <i class="bi bi-save"></i> Enregistrer
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>
