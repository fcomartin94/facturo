<?php
declare(strict_types=1);

namespace Facturo\Database;

use PDO;
use PDOException;

/**
 * Gestiona la conexión PDO compartida mediante el patrón Singleton.
 * Garantiza que solo existe una instancia de PDO durante todo el ciclo
 * de vida de la petición HTTP, evitando conexiones redundantes a la BD.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Devuelve la instancia única de PDO, creándola si aún no existe.
     *
     * @return PDO Conexión activa a la base de datos.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $name = $_ENV['DB_NAME'] ?? 'facturo_db';
        $user = $_ENV['DB_USER'] ?? 'facturo_user';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES      => false,                   // prepared statements reales (más seguro)
            ]);
            return $pdo;
        }   catch (PDOException $e) {
            // No exponemos el DSN en el mensaje de error de usuario
            throw new \RuntimeException('No se pudo conectar a la base de datos', 0, $e);
        }
    }
    /** Impide instanciación directa y clonación. */
    private function __construct() {}
    private function __clone() {}
}