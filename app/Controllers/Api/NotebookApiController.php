<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\AnneeRepository;
use App\Repositories\ClasseRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\NotebookRepository as NotebookReportingRepository;
use App\Services\NotebookService;

final class NotebookApiController extends Controller
{
    private NotebookService $service;

    public function __construct()
    {
        $pdo = Database::pdo();

        $this->service = new NotebookService(
            new AnneeRepository($pdo),
            new ClasseRepository($pdo),
            new ProgrammeRepository($pdo),
            new NotebookReportingRepository($pdo),
        );
    }

    public function global(): void
    {
        $filters = [
            'annee_id'     => $_GET['annee_id']     ?? null,
            'classe_id'    => $_GET['classe_id']    ?? null,
            'date_from'    => $_GET['date_from']    ?? null,
            'date_to'      => $_GET['date_to']      ?? null,
            'only_orphans' => isset($_GET['only_orphans']) && $_GET['only_orphans'] !== '0',
        ];
        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        $data = $this->service->getGlobalNotebookMatrix($filters);

        $this->json([
            'ok' => true,
            'stats' => $data['stats'],
            'items' => $data['items'],
        ]);
    }

    public function confirmPrint(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);

        $ids = $json['ids'] ?? [];
        if (!is_array($ids)) $ids = [];

        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (empty($ids)) {
            $this->json(['ok' => false, 'error' => 'Aucune séance sélectionnée']);
            return;
        }

        $res = $this->service->confirmPrint($ids);

        $this->json([
            'ok' => true,
            'updated' => (int)($res['updated'] ?? 0),
        ]);
    }
}
