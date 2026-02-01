<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
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

        $this->view('admin/tools/tool', [
            'key' => $key,
            'tools' => $svc->tools(),
            'payload' => $data,
            'ok' => array_key_exists('ok', $_GET),
            'err' => (string)($_GET['err'] ?? ''),
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

            // Default: ok=1 (succès)
            $this->redirect('/admin/tools/' . $key . '?ok=1');
            return;
        }

        $err = $res['error'] ?? 'ERROR';
        $this->redirect('/admin/tools/' . $key . '?err=' . urlencode((string)$err));
    }
}
