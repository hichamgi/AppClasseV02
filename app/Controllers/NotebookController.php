<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\AnneeRepository;
use App\Repositories\ClasseRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\NotebookRepository as NotebookReportingRepository;
use App\Services\NotebookService;

final class NotebookController extends Controller
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
            'date_from'    => $_GET['date_from'] ?? null,
            'date_to'      => $_GET['date_to'] ?? null,
            'classe_id'    => $_GET['classe_id'] ?? null,
            'module_id'    => $_GET['module_id'] ?? null,
        ];

        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        $data = $this->service->getGlobalNotebookMatrix($filters);

        $this->view('notebook/global_matrix', $data);
    }
}
