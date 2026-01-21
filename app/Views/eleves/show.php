<h1 class="h4 mb-3">Détails élève</h1>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold"><?= htmlspecialchars(($eleve['nom'] ?? '') . ' ' . ($eleve['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-muted small">SGS: <?= htmlspecialchars($eleve['numerosgs'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-muted small">Sexe: <?= htmlspecialchars($eleve['sexe'] ?? 'M', ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="fw-semibold mb-2">Actions (exemples API)</div>

        <div class="mb-2 text-muted small">
          Ces boutons montrent comment appeler l’API. Tu peux les déplacer dans une page “Séances”.
        </div>

        <div class="d-grid gap-2">
          <button class="btn btn-outline-danger"
                  onclick="AppClasse.markAbsence({ idseance: 1, ideleve: <?= (int)$eleve['id'] ?>, absent: 1, justify: 0 })">
            Déclarer absent (séance #1)
          </button>
          <button class="btn btn-outline-success"
                  onclick="AppClasse.markAbsence({ idseance: 1, ideleve: <?= (int)$eleve['id'] ?>, absent: 0, justify: 0 })">
            Annuler absence (séance #1)
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Dossiers scolaires</div>

        <?php if (empty($dossiers)): ?>
          <div class="text-muted">Aucun dossier.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Année</th>
                  <th>Points</th>
                  <th>Participation</th>
                  <th>Obs1</th>
                  <th>Obs2</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dossiers as $d): ?>
                  <tr>
                    <td><?= htmlspecialchars($d['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($d['points'] ?? 0) ?></td>
                    <td><?= (int)($d['participation'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($d['obs1'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($d['obs2'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>