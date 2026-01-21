<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Classe;

class ClassesController extends Controller
{
    public function index(): void
    {
        $classes = Classe::all(200);
        $this->view('classes/index', compact('classes'));
    }
}