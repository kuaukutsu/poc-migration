<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Override;
use Throwable;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\driver\DriverType;
use kuaukutsu\poc\migration\internal\command\Args;
use kuaukutsu\poc\migration\internal\command\Command;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\internal\connection\PDO\Connection;

final class CommandTest extends TestCase
{
    private CommandInterface $command;

    #[Override]
    protected function setUp(): void
    {
        $this->command = new Command(
            new Connection(new PDO(dsn: 'sqlite::memory:'), DriverType::PDO_SQLITE),
            new Params(table: 'migration'),
        );
    }

    /**
     * @throws Throwable
     */
    public function testInit(): void
    {
        $this->execInitialization();

        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    /**
     * @throws Throwable
     */
    public function testUp(): void
    {
        $this->execInitialization();

        $this->execUp('table1');

        $data = $this->command->fetchSavedMigrationNames();
        self::assertEquals('test-table1', $data[0]);
    }

    /**
     * @throws Throwable
     */
    public function testDown(): void
    {
        $this->execInitialization();

        $this->execUp('table1');
        $this->execUp('table2');

        $data = $this->command->fetchSavedMigrationNames();
        self::assertContains('test-table1', $data);
        self::assertCount(2, $data);

        $this->execDown('table1');
        $data = $this->command->fetchSavedMigrationNames();
        self::assertContains('test-table2', $data);
        self::assertCount(1, $data);

        $this->execDown('table2');
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    /**
     * @throws Throwable
     */
    public function testFetchLimit(): void
    {
        $this->execInitialization();

        $this->execUp('table1');
        $this->execUp('table2');
        $this->execUp('table3');

        $data = $this->command->fetchSavedMigrationNames(
            new Args(limit: 1)
        );
        self::assertCount(1, $data);
        self::assertEquals('test-table3', $data[0]);

        $data = $this->command->fetchSavedMigrationNames(
            new Args(limit: 2)
        );
        self::assertCount(2, $data);
        self::assertEquals('test-table3', $data[0]);
        self::assertEquals('test-table2', $data[1]);
    }

    /**
     * @throws Throwable
     */
    public function testPDOException(): void
    {
        $this->expectException(PDOException::class);
        $this->execUp('table1');
    }

    /**
     * @throws Throwable
     */
    private function execInitialization(string $tableName = 'migration'): void
    {
        $queryString = <<<SQL
CREATE TABLE IF NOT EXISTS $tableName
(
    name TEXT PRIMARY KEY,
    atime TEXT
)
SQL;
        $this->command->exec(
            queryString: $queryString,
            filename: 'test',
        );
    }

    /**
     * @throws Throwable
     */
    private function execUp(string $tableName): void
    {
        $queryString = <<<SQL
CREATE TABLE IF NOT EXISTS $tableName
(
    name TEXT PRIMARY KEY
)
SQL;
        $this->command->up(
            queryString: $queryString,
            filename: 'test-' . $tableName,
        );
    }

    /**
     * @throws Throwable
     */
    private function execDown(string $tableName): void
    {
        $queryString = <<<SQL
DROP TABLE IF EXISTS $tableName
SQL;
        $this->command->down(
            queryString: $queryString,
            filename: 'test-' . $tableName,
        );
    }
}
