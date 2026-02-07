<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Core\Container;

class App
{
    private Router $router;
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/app.php';
        date_default_timezone_set($this->config['timezone'] ?? 'UTC');

        // DB singleton init
        Database::init(require dirname(__DIR__) . '/config/database.php');

        $container = new Container(Database::pdo());

        $this->router = new Router($this->config['base_url'] ?? '', $container);

        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->router->dispatch();
    }

    private function registerRoutes(): void
    {
        // Web
        $this->router->get('/login', 'App\\Controllers\\AuthController@loginForm');
        $this->router->post('/login', 'App\\Controllers\\AuthController@login');
        $this->router->get('/twofa', 'App\\Controllers\\AuthController@twofaForm');
        $this->router->post('/twofa', 'App\\Controllers\\AuthController@twofaVerify');
        $this->router->get('/logout', 'App\\Controllers\\AuthController@logout');

        $this->router->get('/', 'App\\Controllers\\DashboardController@index', [AuthMiddleware::class]);
        $this->router->get('/dashboard', 'App\\Controllers\\DashboardController@index', [AuthMiddleware::class]);

        $this->router->get('/classes', 'App\\Controllers\\ClassesController@index', [AuthMiddleware::class]);
        $this->router->get('/eleves', 'App\\Controllers\\ElevesController@index', [AuthMiddleware::class]);
        $this->router->get('/eleves/{id}', 'App\\Controllers\\ElevesController@show', [AuthMiddleware::class]);

        $this->router->get('/seances/{id}', 'App\\Controllers\\SeancesController@show', [AuthMiddleware::class]);

        // API (JSON)
        $this->router->get('/api/classes', 'App\\Controllers\\Api\\ClassesApiController@index', [AuthMiddleware::class]);
        $this->router->get('/api/eleves', 'App\\Controllers\\Api\\ElevesApiController@index', [AuthMiddleware::class]);
        $this->router->get('/api/eleves/{id}', 'App\\Controllers\\Api\\ElevesApiController@show', [AuthMiddleware::class]);


        // Actions asynchrones (absences / parties)
        $this->router->post('/api/seances/absence', 'App\\Controllers\\Api\\ElevesApiController@markAbsence', [AuthMiddleware::class]);
        $this->router->post('/api/seances/partie', 'App\\Controllers\\Api\\ElevesApiController@attachPartie', [AuthMiddleware::class]);
        $this->router->post('/api/seances/observation', 'App\\Controllers\\Api\\SeancesApiController@updateObservation', [AuthMiddleware::class]);
        $this->router->post('/api/seances/partie/delete', 'App\\Controllers\\Api\\SeancesApiController@detachPartie', [AuthMiddleware::class]);



        // Modals (HTML partials)
        $this->router->get('/modals/seances/new', 'App\\Controllers\\ModalController@newSeance', [AuthMiddleware::class]);
        $this->router->get('/modals/seances/absences', 'App\\Controllers\\ModalController@absences', [AuthMiddleware::class]);
        $this->router->get('/modals/seances/parties', 'App\\Controllers\\ModalController@parties', [AuthMiddleware::class]);
        $this->router->get('/modals/eleves/tags', 'App\\Controllers\\ModalController@eleveTags', [AuthMiddleware::class]);
        $this->router->get('/modals/eleves/show', 'App\\Controllers\\ModalController@eleveShow', [AuthMiddleware::class]);
        $this->router->get('/modals/absences/list', 'App\\Controllers\\ModalController@absencesList', [AuthMiddleware::class]);
        $this->router->get('/modals/eleves/notebook', 'App\\Controllers\\ModalController@eleveNotebook', [AuthMiddleware::class]);


        // API actions (JSON)
        $this->router->post('/api/seances/create', 'App\\Controllers\\Api\\SeancesApiController@create', [AuthMiddleware::class]);
        $this->router->post('/api/seances/create-bulk', 'App\\Controllers\\Api\\SeancesApiController@createBulk', [AuthMiddleware::class]);
        $this->router->post('/api/eleves/tags', 'App\\Controllers\\Api\\ElevesApiController@setTags', [AuthMiddleware::class]);
        $this->router->post('/api/points/update', 'App\\Controllers\\Api\\ElevesApiController@updatePoints', [AuthMiddleware::class]);
        $this->router->post('/api/tags/create', 'App\\Controllers\\Api\\ElevesApiController@createTag', [AuthMiddleware::class]);
        $this->router->post('/api/eleves/notebook/update', 'App\\Controllers\\Api\\ElevesApiController@updateNotebook', [AuthMiddleware::class]);

        // Admin
        $this->router->get('/admin', 'App\\Controllers\\Admin\\AdminDashboardController@index', [AuthMiddleware::class, AdminMiddleware::class]);
        $this->router->get('/admin/tools', 'App\\Controllers\\Admin\\AdminToolsController@index', [AuthMiddleware::class, AdminMiddleware::class]);
        $this->router->get('/admin/tools/{key}', 'App\\Controllers\\Admin\\AdminToolsController@tool', [AuthMiddleware::class, AdminMiddleware::class]);
        $this->router->post('/admin/tools/{key}', 'App\\Controllers\\Admin\\AdminToolsController@toolPost', [AuthMiddleware::class, AdminMiddleware::class]);


        $this->router->get('/notebook/global', 'App\\Controllers\\NotebookController@global', [AuthMiddleware::class]);
        $this->router->get('/api/notebook/global', 'App\\Controllers\\Api\\NotebookApiController@global', [AuthMiddleware::class]);

        // Cahier - Impression
        $this->router->get('/notebook/print', 'App\\Controllers\\NotebookController@print', [AuthMiddleware::class]);
        $this->router->post('/api/notebook/print/confirm', 'App\\Controllers\\Api\\NotebookApiController@confirmPrint', [AuthMiddleware::class]);
    }
}
