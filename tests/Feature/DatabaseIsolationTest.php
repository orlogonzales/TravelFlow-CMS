<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    /**
     * Verifica que el entorno de pruebas está conectado estrictamente a MySQL 'tf_cms_test'.
     */
    public function test_testing_environment_uses_mysql_and_isolated_tf_cms_test_database(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        $this->assertSame('mysql', $driver, 'El driver de base de datos en testing debe ser estrictamente MySQL.');
        $this->assertSame('tf_cms_test', $database, 'La base de datos de testing debe ser estrictamente tf_cms_test.');
        $this->assertNotSame('tf_cms', $database, 'La suite de pruebas jamás debe conectarse a la base de datos de desarrollo tf_cms.');

        // Comprobación directa contra la conexión PDO activa
        $currentDb = DB::selectOne('SELECT DATABASE() as db')->db;
        $this->assertSame('tf_cms_test', $currentDb, 'La consulta directa SELECT DATABASE() debe retornar tf_cms_test.');
    }
}
