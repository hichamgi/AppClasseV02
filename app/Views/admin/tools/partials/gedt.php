<?php
$annee = $data['annee'] ?? null;
$classes = $data['classes'] ?? [];
$grid = $data['grid'] ?? [];

$days = [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'];
$hours = ['08:30:00','09:30:00','10:30:00','11:30:00','14:30:00','15:30:00','16:30:00','17:30:00'];
?>

<?php if (!$annee): ?>
  <div class="alert alert-warning">Aucune année scolaire active.</div>
<?php else: ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="<?= $baseUrl ?>/admin/tools/gedt">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:110px">Heure</th>
              <?php foreach ($days as $n => $label): ?>
                <th><?= htmlspecialchars($label) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hours as $h): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars(substr($h,0,5)) ?></td>
                <?php foreach ($days as $n => $label): ?>
                  <?php $selected = (int)($grid[$n][$h] ?? 0); ?>
                  <td>
                    <select class="form-select form-select-sm" name="grid[<?= (int)$n ?>][<?= htmlspecialchars($h) ?>]">
                      <option value="0">-- libre --</option>
                      <?php foreach ($classes as $c): ?>
                        <?php $idc = (int)$c['id']; ?>
                        <option value="<?= $idc ?>" <?= ($idc === $selected) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($c['classe'] ?? '') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                <?php endforeach; ?>
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
