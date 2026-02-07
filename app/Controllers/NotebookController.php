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
use App\Models\User;

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

    public function print(): void
    {
        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];
        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        $data = $this->service->getPrintNotebookData($filters);

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $data['profName'] = $userId > 0 ? \App\Models\User::profName($userId) : '';

        // ✅ Année scolaire DYNAMIQUE depuis la DB
        // Si ton AnneeRepository a une méthode getCurrent(), utilise-la.
        // Sinon, on met juste vide et la view affichera "—".
        $pdo = \App\Core\Database::pdo();
        $anneeRepo = new \App\Repositories\AnneeRepository($pdo);

        $data['annee_label'] = '';
        if (method_exists($anneeRepo, 'getCurrent')) {
            $data['annee_label'] = (string)($anneeRepo->getCurrent() ?? '');
        }

        // Matière/Lycée : si tu veux aussi en DB plus tard, on peut les déplacer.
        $data['matiere'] = 'Informatique';
        $data['lycee'] = 'Lycée Ibn Al Haytam – Fès -';

        $this->view('notebook/print', $data);
    }
}
