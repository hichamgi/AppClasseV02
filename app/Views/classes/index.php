<h1 class="h3 mb-3">Classes</h1>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Classe</th>
          <th>Année (idannee)</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (($classes ?? []) as $c): ?>
        <tr>
          <td><?= (int)$c['id'] ?></td>
          <td><?= htmlspecialchars($c['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int)($c['idannee'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>