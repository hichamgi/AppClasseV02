<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Eleve;
use App\Services\AcademicService;

class ElevesController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $eleves = $q !== '' ? Eleve::search($q, 100) : Eleve::all(100);
        $this->view('eleves/index', compact('eleves', 'q'));
    }

    public function show(array $args): void
    {
        $id = (int)($args['id'] ?? 0);
        $eleve = Eleve::find($id);
        if (!$eleve) {
            http_response_code(404);
            echo "Élève introuvable";
            return;
        }

        $svc = new AcademicService();
        $dossiers = $svc->eleveDossiers($id);

        $this->view('eleves/show', compact('eleve', 'dossiers'));
    }
}
