<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->ensureSafeTestingEnvironment(
            $app
        );

        return $app;
    }

    private function ensureSafeTestingEnvironment(
        Application $app
    ): void {
        /*
         * Un cache de configuration peut empêcher
         * phpunit.xml de remplacer la configuration
         * locale de la base.
         *
         * On refuse donc catégoriquement de lancer
         * les tests tant qu'un config:cache est actif.
         */
        if ($app->configurationIsCached()) {
            throw new RuntimeException(
                'SECURITE TESTS : la configuration Laravel est en cache. '
                .'Exécute "php artisan config:clear" avant de lancer les tests.'
            );
        }

        $environment =
            $app->environment();

        $connection =
            (string) $app['config']->get(
                'database.default'
            );

        $database =
            (string) $app['config']->get(
                "database.connections.{$connection}.database"
            );

        /*
         * Aucun test Laravel ne doit démarrer
         * en dehors de l'environnement "testing".
         */
        if ($environment !== 'testing') {
            throw new RuntimeException(
                'SECURITE TESTS : environnement interdit. '
                ."APP_ENV vaut \"{$environment}\" au lieu de \"testing\"."
            );
        }

        /*
         * Protection absolue contre MySQL / MariaDB /
         * PostgreSQL ou une base SQLite persistante.
         *
         * Les tests de ce projet doivent utiliser
         * uniquement SQLite en mémoire.
         */
        if (
            $connection !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new RuntimeException(
                'SECURITE TESTS : base de données interdite. '
                ."Connexion détectée : \"{$connection}\", "
                ."base détectée : \"{$database}\". "
                .'Les tests doivent utiliser exclusivement '
                .'DB_CONNECTION=sqlite et DB_DATABASE=:memory:.'
            );
        }
    }
}
