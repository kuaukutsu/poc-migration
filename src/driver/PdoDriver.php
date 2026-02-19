<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\driver;

use Override;
use Closure;
use PDO;
use PDOException;
use kuaukutsu\poc\migration\connection\ConnectionInterface;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\internal\command\Command;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\internal\connection\PDO\Connection;

final class PdoDriver implements DriverInterface
{
    /**
     * @var Closure():PDO
     */
    private readonly Closure $connectionFactory;

    private readonly DriverType $driver;

    private int $connectionTimer = 0;

    private ?Connection $connectionInstance = null;

    /**
     * @param non-empty-string $dsn
     * @param non-empty-string|null $username
     * @param non-empty-string|null $password
     * @throws ConfigurationException
     * @phpstan-ignore missingType.iterableValue
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
    ) {
        if (extension_loaded('PDO') === false) {
            throw new ConfigurationException(
                'PDO: extension not loaded.'
            );
        }

        $this->connectionFactory = static fn(): PDO => new PDO($dsn, $username, $password, $options);
        $this->driver = match (true) {
            str_starts_with($dsn, 'mysql:') => DriverType::PDO_MYSQL,
            str_starts_with($dsn, 'pgsql:') => DriverType::PDO_PGSQL,
            str_starts_with($dsn, 'sqlite:') => DriverType::PDO_SQLITE,
            default => throw new ConfigurationException(
                'PDODriver: is not implemented.'
            ),
        };
    }

    #[Override]
    public function getName(): string
    {
        return $this->driver->value();
    }

    #[Override]
    public function getSetupPath(): string
    {
        return dirname(__DIR__) . sprintf('/connection/%s/migration/', $this->driver->value());
    }

    #[Override]
    public function makeCommand(Params $params): CommandInterface
    {
        return new Command($this->makeConnection(), $params);
    }

    /**
     * @throws ConnectionException
     */
    private function makeConnection(): ConnectionInterface
    {
        $timeout = 300;

        /**
         * @note если вдруг экземпляр класса Migrator будет использоваться как сервис, то переиспользуем коннект.
         * Но с ограничением по времени, долго держать в памяти не будем.
         */
        if ($this->connectionInstance === null || $this->connectionTimer < time()) {
            $this->connectionTimer = time() + $timeout;
            try {
                return $this->connectionInstance = new Connection(
                    ($this->connectionFactory)(),
                    $this->driver,
                );
            } catch (PDOException $exception) {
                throw new ConnectionException($this->driver, $exception);
            }
        }

        return $this->connectionInstance;
    }
}
