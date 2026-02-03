<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\Admin\AdminToolsService;

class AdminToolsController extends Controller
{
    public function index(): void
    {
        $svc = new AdminToolsService();

        $this->view('admin/tools/index', [
            'tools' => $svc->tools(),
        ]);
    }

    public function tool(array $args): void
    {
        $key = (string)($args['key'] ?? '');
        $svc = new AdminToolsService();

        $data = $svc->dataForTool($key);
        if (!empty($data['_notfound'])) {
            http_response_code(404);
            echo "404 Tool not found";
            return;
        }

        $report = null;
        if ($key === 'upstudents') {
            $raw = \App\Core\Session::flash('admin_report_upstudents');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $report = $decoded;
            }
        }

        // ✅ Modal: add student
        if ($key === 'upstudents' && (string)($_GET['modal'] ?? '') === 'add') {
            // Important: render modal layout (no navbar)
            $this->view('admin/tools/modals/eleve_add', [
                'classes' => $data['classes'] ?? [],
            ], 'modal'); // ou layout: null selon ta signature
            return;
        }

        $this->view('admin/tools/tool', [
            'key' => $key,
            'tools' => $svc->tools(),
            'payload' => $data,
            'ok' => array_key_exists('ok', $_GET),
            'err' => (string)($_GET['err'] ?? ''),
            'report' => $report,
        ]);
    }

    public function toolPost(array $args): void
    {
        $token = (string)($_POST['_csrf'] ?? '');
        if (!Csrf::check($token)) {
            http_response_code(419);
            echo "CSRF token invalid";
            return;
        }

        $key = (string)($args['key'] ?? '');
        $svc = new AdminToolsService();

        $res = $svc->handlePost($key, $_POST);

        if (!empty($res['ok'])) {

            // Special-case: ramadan -> ok = valeur ramadan (0/1)
            if ($key === 'ramadan') {
                $val = isset($_POST['ramadan']) ? (int)$_POST['ramadan'] : 0;
                $val = ($val === 1) ? 1 : 0;

                $this->redirect('/admin/tools/ramadan?ok=' . $val);
                return;
            }

            // Flash report (for tools that return details)
            if ($key === 'upstudents') {
                // On stocke tout le résultat pour affichage après redirect
                Session::flash('admin_report_upstudents', json_encode($res, JSON_UNESCAPED_UNICODE));
            }


            // Default: ok=1 (succès)
            $this->redirect('/admin/tools/' . $key . '?ok=1');
            return;
        }

        $err = $res['error'] ?? 'ERROR';
        $this->redirect('/admin/tools/' . $key . '?err=' . urlencode((string)$err));
    }
}
