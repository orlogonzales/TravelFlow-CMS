<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SecurityGuardTest extends TestCase
{
    /**
     * Verifica que el guard de seguridad aborta de forma tajante si la base de datos es distinta a tf_cms_test.
     */
    public function test_security_guard_aborts_if_database_is_not_tf_cms_test(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("SEGURIDAD TEST ABORTADA: La base de datos conectada en testing es 'tf_cms'");

        try {
            config(['database.connections.mysql.database' => 'tf_cms']);
            DB::purge('mysql');
            $this->ensureTestingDatabase();
        } finally {
            // Restaurar siempre la conexión a tf_cms_test
            config(['database.connections.mysql.database' => 'tf_cms_test']);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }

    /**
     * Verifica que el guard de seguridad aborta de forma tajante si APP_ENV no es 'testing'.
     */
    public function test_security_guard_aborts_if_app_env_is_not_testing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("SEGURIDAD TEST ABORTADA: APP_ENV debe ser estrictamente 'testing'");

        try {
            config(['app.env' => 'production']);
            $this->ensureTestingDatabase();
        } finally {
            config(['app.env' => 'testing']);
        }
    }
}
