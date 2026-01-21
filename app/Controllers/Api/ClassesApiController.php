<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Classe;

class ClassesApiController extends Controller
{
    public function index(): void
    {
        $rows = Classe::all(500);
        $this->json(['ok' => true, 'data' => $rows]);
    }
}