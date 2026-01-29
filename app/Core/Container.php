<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Container
{
    /** @var array<class-string, object> */
    private array $instances = [];

    public function __construct(private PDO $pdo) {}

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function has(string $class): bool
    {
        return isset($this->instances[$class]) || $this->canBuild($class);
    }

    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        // 🔥 mapping explicite (tu ajoutes au fur et à mesure)
        $obj = match ($class) {

            // Exemple : Notebook global (à créer)
            \App\Repositories\NotebookRepository::class =>
                new \App\Repositories\NotebookRepository($this->pdo),

            \App\Services\NotebookService::class =>
                new \App\Services\NotebookService(
                    $this->get(\App\Repositories\NotebookRepository::class)
                ),

            \App\Controllers\NotebookController::class =>
                new \App\Controllers\NotebookController(
                    $this->get(\App\Services\NotebookService::class)
                ),

            \App\Controllers\Api\NotebookApiController::class =>
                new \App\Controllers\Api\NotebookApiController(
                    $this->get(\App\Services\NotebookService::class)
                ),

            default => new $class(), // fallback safe
        };

        return $this->instances[$class] = $obj;
    }

    private function canBuild(string $class): bool
    {
        // Ici, on autorise le fallback new $class() si la classe existe
        return class_exists($class);
    }
}
