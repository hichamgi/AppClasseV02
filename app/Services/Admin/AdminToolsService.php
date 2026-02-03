<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Auth;
use App\Repositories\Admin\AdminToolsRepository;
use App\Services\DashboardService;

class AdminToolsService
{
    public function __construct(
        private AdminToolsRepository $repo = new AdminToolsRepository(),
        private DashboardService $dashboard = new DashboardService()
    ) {}

    public function tools(): array
    {
        return [
            'classes' => ['title' => "Classes de l'année", 'icon' => 'collection', 'desc' => "Créer/renommer les classes."],
            'students' => ['title' => "Élèves par classe", 'icon' => 'people', 'desc' => "Lister les élèves par classe."],
            'ramadan' => ['title' => "Ramadan ON/OFF", 'icon' => 'moon-stars', 'desc' => "Basculer le flag Ramadan."],
            'gedt' => ['title' => "Gestion de l'emploi du temps", 'icon' => 'calendar-week', 'desc' => "Affectation et modification de l'emploi du temps."],
            'upstudents' => ['title'=>"Importations des élèves", 'icon'=>'download', 'desc'=>"Importer une liste ou ajouter via modal."],
            'password' => ['title' => "Mot de passe", 'icon' => 'key', 'desc' => "Changer le mot de passe admin."],
        ];
    }

    public function dataForTool(string $key): array
    {
        $annee = $this->dashboard->currentAnnee();

        return match ($key) {

            'classes' => [
                'annee' => $annee,
                'classes' => $annee ? $this->repo->listClassesForAnnee((int)$annee['id']) : [],
            ],

            'students' => (function () use ($annee) {

                $classes = $annee ? $this->repo->listClassesForAnnee((int)$annee['id']) : [];

                $selected = (int)($_GET['classe'] ?? 0);

                $rows = ($selected > 0)
                    ? $this->repo->listElevesByClasse($selected)
                    : [];

                return [
                    'annee' => $annee,
                    'classes' => $classes,
                    'selectedClasse' => $selected,
                    'rows' => $rows,
                ];
            })(),

            'ramadan' => [
                'ramadan' => $this->repo->getRamadanFlag(\App\Core\Auth::id()),
            ],

            'gedt' => (function () use ($annee) {

                $classes = $annee ? $this->repo->listClassesForAnnee((int)$annee['id']) : [];

                $rows = ($annee)
                    ? $this->repo->listEdtForAnnee((int)$annee['id'])
                    : [];

                // grid[n][heure] = idclasse
                $grid = [];
                foreach ($rows as $r) {
                    $n = (int)$r['n'];
                    $h = (string)$r['heure'];
                    $grid[$n][$h] = (int)$r['idclasse'];
                }

                return [
                    'annee' => $annee,
                    'classes' => $classes,
                    'grid' => $grid,
                ];
            })(),

            'upstudents' => [
                'annee' => $annee,
                'classes' => $annee ? $this->repo->listClassesForAnnee((int)$annee['id']) : [],
            ],

            'password' => [],

            default => ['_notfound' => true],
        };
    }


    public function handlePost(string $key, array $post): array
    {
        return match ($key) {
            'ramadan' => $this->postRamadan($post),
            'password' => $this->postPassword($post),
            'classes' => $this->postClasses($post),
            'gedt' => $this->postEdt($post),
            'upstudents' => $this->postUpStudents($post),
            default => ['ok' => false, 'error' => 'UNKNOWN_TOOL'],
        };
    }

    private function postRamadan(array $post): array
    {
        $val = isset($post['ramadan']) ? (int)$post['ramadan'] : 0;
        $this->repo->setRamadanFlag(Auth::id(), $val);
        return ['ok' => true];
    }

    private function postPassword(array $post): array
    {
        $old = (string)($post['old'] ?? '');
        $new = (string)($post['new'] ?? '');
        $new2 = (string)($post['new2'] ?? '');

        if ($new === '' || $new !== $new2) {
            return ['ok' => false, 'error' => 'PASSWORD_MISMATCH'];
        }

        $hash = $this->repo->getUserPasswordHash(Auth::id());
        if ($hash === '' || !password_verify($old, $hash)) {
            return ['ok' => false, 'error' => 'OLD_PASSWORD_INVALID'];
        }

        $this->repo->updatePasswordHash(Auth::id(), password_hash($new, PASSWORD_DEFAULT));
        return ['ok' => true];
    }

    private function postClasses(array $post): array
    {
        $annee = $this->dashboard->currentAnnee();
        if (!$annee) return ['ok' => false, 'error' => 'NO_ANNEE'];

        $idannee = (int)$annee['id'];

        $new = trim((string)($post['new_classe'] ?? ''));
        if ($new !== '') {
            $this->repo->createClasse($idannee, $new);
        }

        if (!empty($post['classe']) && is_array($post['classe'])) {
            foreach ($post['classe'] as $id => $name) {
                $id = (int)$id;
                $name = trim((string)$name);
                if ($id > 0 && $name !== '') {
                    $this->repo->updateClasseName($id, $name);
                }
            }
        }

        return ['ok' => true];
    }

    private function postEdt(array $post): array
    {
        $annee = $this->dashboard->currentAnnee();
        if (!$annee) return ['ok' => false, 'error' => 'NO_ANNEE'];

        $grid = $post['grid'] ?? null;
        if (!is_array($grid)) return ['ok' => false, 'error' => 'BAD_GRID'];

        $this->repo->saveEdtGrid((int)$annee['id'], $grid);
        return ['ok' => true];
    }

    private function postUpStudents(array $post): array
    {
        $annee = $this->dashboard->currentAnnee();
        if (!$annee) return ['ok' => false, 'error' => 'NO_ANNEE'];

        $mode = (string)($post['mode'] ?? 'list'); // 'list' ou 'single'
        $svc = new \App\Services\Admin\AdminStudentsService();

        if ($mode === 'single') {
            return $svc->addOneFull((int)$annee['id'], $post);
        }

        $raw = (string)($post['list'] ?? '');
        return $svc->importList((int)$annee['id'], $raw);
    }

}