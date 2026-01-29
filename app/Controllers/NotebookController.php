<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\NotebookService;

final class NotebookController extends Controller
{
    public function __construct(private NotebookService $service) {}

    public function global(): void
    {
        $filters = [
            'annee_id'  => $_GET['annee_id'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? null,
            'module_id' => $_GET['module_id'] ?? null, // optionnel (programme info)
            'date_from' => $_GET['date_from'] ?? null,
            'date_to'   => $_GET['date_to'] ?? null,
        ];
        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        $data = $this->service->getGlobalNotebookMatrix($filters);

        $this->view('notebook/global_matrix', $data);
    }
}