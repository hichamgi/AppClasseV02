<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\NotebookService;

final class NotebookApiController extends Controller
{
    public function __construct(private NotebookService $service) {}

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

        $data = $this->service->getGlobalNotebook($filters);

        $this->json([
            'ok' => true,
            'stats' => $data['stats'],
            'items' => $data['items'],
        ]);
    }
}
