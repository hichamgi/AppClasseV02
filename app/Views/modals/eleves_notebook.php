<?php
$ideleve = (int)$eleve['id'];
$idannee = (int)$idannee;
$idacademic = (int)$idacademicrecords;
?>

<div class="modal-header">
  <h5 class="modal-title">
    Suivi cahier — <?= $this->e(trim(($eleve['nom'] ?? '').' '.($eleve['prenom'] ?? ''))) ?>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
  <div class="small text-muted mb-2">Cours (sur 10) + Exercices (sur 10)</div>

  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Module</th>
          <th style="width:140px">Cours /10</th>
          <th style="width:140px">Exercices /10</th>
          <th style="width:120px">Total /20</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $idmodule = (int)$r['idmodule'];
            $cours = (float)($r['score_cours'] ?? 0);
            $exo   = (float)($r['score_exercices'] ?? 0);
            $total = $cours + $exo;
            $label = trim(($r['abrev'] ?? '').' - '.($r['module'] ?? ''), " -");
          ?>
          <tr data-idmodule="<?= $idmodule ?>">
            <td><?= $this->e($label) ?></td>

            <td>
              <input type="number" class="form-control form-control-sm js-nb-cours"
                     min="0" max="10" step="0.5"
                     value="<?= $this->e((string)$cours) ?>">
            </td>

            <td>
              <input type="number" class="form-control form-control-sm js-nb-exo"
                     min="0" max="10" step="0.5"
                     value="<?= $this->e((string)$exo) ?>">
            </td>

            <td class="fw-semibold js-nb-total"><?= $this->e(number_format($total, 1)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="alert alert-danger d-none mt-3" id="nbErr"></div>
  <div class="alert alert-success d-none mt-3" id="nbOk"></div>
</div>

<div class="modal-footer">
  <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
  <button class="btn btn-primary"
          id="btnNotebookSave"
          data-ideleve="<?= $ideleve ?>"
          data-idannee="<?= $idannee ?>"
          data-idacademic="<?= $idacademic ?>">
    Enregistrer
  </button>
</div>
