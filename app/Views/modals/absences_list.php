<div class="modal-header">
  <h5 class="modal-title">
    Absences — année <?= (int)$idannee ?>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<div class="modal-body">
  <div class="mb-2">
    <div class="fw-semibold">
      <?= htmlspecialchars(trim(($eleve['nom'] ?? '').' '.($eleve['prenom'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div dir="rtl" class="text-muted">
      <?= htmlspecialchars(trim(($eleve['nomar'] ?? '').' '.($eleve['prenomar'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="text-muted">Aucune absence enregistrée pour cette année.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Heure</th>
            <th>Classe</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars((string)$r['date'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)$r['heured'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)$r['classe'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary"
                   href="<?= $baseUrl ?>/seances/<?= (int)$r['idseance'] ?>">
                  Ouvrir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
</div>