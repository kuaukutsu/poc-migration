<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use Override;
use PDO;
use PDOException;
use Closure;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\connection\mysql\MysqlCommand;
use kuaukutsu\poc\migration\connection\pgsql\PgsqlCommand;
use kuaukutsu\poc\migration\connection\sqlite\SqliteCommand;
use kuaukutsu\poc\migration\ConnectionDriver;

final class PdoDriver implements Driver
{
    /**
     * @var Closure():PDO
     */
    private readonly Closure $connectionFactory;

    private readonly ConnectionDriver $driver;

    private int $connectionTimer = 0;

    private ?PDO $connectionInstance = null;

    /**
     * @param non-empty-string $dsn
     * @param non-empty-string|null $username
     * @param non-empty-string|null $password
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
    ) {
        $this->connectionFactory = static fn(): PDO => new PDO($dsn, $username, $password, $options);
        $this->driver = match (true) {
            str_starts_with($dsn, 'mysql:') => ConnectionDriver::PDO_MYSQL,
            str_starts_with($dsn, 'pgsql:') => ConnectionDriver::PDO_PGSQL,
            str_starts_with($dsn, 'sqlite:') => ConnectionDriver::PDO_SQLITE,
            default => ConnectionDriver::UNSUPPORTED,
        };
    }

    #[Override]
    public function getType(): ConnectionDriver
    {
        return $this->driver;
    }

    #[Override]
    public function makeCommand(Params $params): Command
    {
        return match ($this->driver) {
            ConnectionDriver::PDO_MYSQL => new MysqlCommand($this->makePDOConnection(), $params),
            ConnectionDriver::PDO_PGSQL => new PgsqlCommand($this->makePDOConnection(), $params),
            ConnectionDriver::PDO_SQLITE => new SqliteCommand($this->makePDOConnection(), $params),
            ConnectionDriver::UNSUPPORTED => throw new ConfigurationException(
                'PDODriver: is not implemented.'
            ),
        };
    }

    /**
     * @throws ConnectionException
     */
    private function makePDOConnection(): PDO
    {
        /**
         * @note если вдруг экземпляр класса Migrator будет использоваться как сервис, то переиспользуем коннект.
         * Но с ограничением по времени, долго держать в памяти не будем.
         */
        if ($this->connectionInstance === null || $this->connectionTimer < time()) {
            $this->connectionTimer = time() + 300;
            try {
                return $this->connectionInstance = ($this->connectionFactory)();
            } catch (PDOException $exception) {
                throw new ConnectionException($this->driver, $exception);
            }
        }

        return $this->connectionInstance;
    }
}
