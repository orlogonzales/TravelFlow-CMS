<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTestingDatabase();
    }

    /**
     * Barrera de seguridad estricta para impedir ejecuciones destructivas
     * contra la base de datos de desarrollo (tf_cms) o entornos no autorizados.
     */
    protected function ensureTestingDatabase(): void
    {
        $appEnv = config('app.env');
        if ($appEnv !== 'testing') {
            throw new RuntimeException(
                "SEGURIDAD TEST ABORTADA: APP_ENV debe ser estrictamente 'testing' (detectado: '{$appEnv}')."
            );
        }

        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();

        if ($databaseName !== 'tf_cms_test') {
            throw new RuntimeException(
                "SEGURIDAD TEST ABORTADA: La base de datos conectada en testing es '{$databaseName}'. " .
                "Se requiere estrictamente 'tf_cms_test' para prevenir daños o mutaciones accidentales sobre la base de desarrollo 'tf_cms'."
            );
        }
    }
}
