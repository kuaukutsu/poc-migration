<?php

/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\tests\stub\TestDriver;
use kuaukutsu\poc\migration\tests\stub\TestStorage;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\Migrator;

final class MigrationTest extends TestCase
{
    private Migrator $migrator;

    private TestStorage $storage;

    protected function setUp(): void
    {
        $driver = new TestDriver(
            $this->storage = new TestStorage()
        );

        $this->migrator = MigratorFactory::makeFromDriver($driver);
    }

    public function testInit(): void
    {
        $this->migrator->init();

        self::assertEmpty($this->storage->getMigration());
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS migration',
            $this->storage->get('setup.sql') ?? '',
        );
    }

    public function testUp(): void
    {
        $this->migrator->up();

        self::assertContains('202501011024_entity_create.sql', $this->storage->getMigration());
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS entity',
            $this->storage->get('202501011024_entity_create.sql') ?? '',
        );

        self::assertStringNotContainsString(
            "DROP TABLE IF EXISTS entity",
            $this->storage->get('202501011024_entity_create.sql') ?? '',
        );

        // SKIP
        self::assertEquals('SKIP', $this->storage->get('202501011026_entity_duplicate.sql') ?? 'SKIP');

        // repeatable
        self::assertStringContainsString(
            "INSERT INTO entity (name) VALUES ('test');",
            $this->storage->get('202501011024_entity_correction.sql') ?? '',
        );

        // repeatable
        self::assertStringContainsString(
            "INSERT INTO entity (name) VALUES ('test22');",
            $this->storage->get('202501011024_entity_correction_2.sql') ?? '',
        );

        // repeatable SKIP
        self::assertStringNotContainsString(
            "INSERT INTO entity (name) VALUES ('test33');",
            $this->storage->get('202501011024_entity_correction_2.sql') ?? '',
        );
    }

    public function testDown(): void
    {
        $this->migrator->up();
        $this->migrator->down();

        self::assertEmpty($this->storage->getMigration());
        self::assertStringContainsString(
            'DROP TABLE IF EXISTS entity',
            $this->storage->get('202501011024_entity_create.sql') ?? '',
        );
    }

    public function testDownEmpty(): void
    {
        $this->migrator->down();

        self::assertEmpty($this->storage->getMigration());
        self::assertEmpty($this->storage->get('202501011024_entity_create.sql'));
    }

    public function testFixture(): void
    {
        $this->migrator->fixture();

        self::assertEmpty($this->storage->getMigration());
        self::assertStringContainsString(
            "INSERT INTO entity (name) VALUES ('fixture');",
            $this->storage->get('202501011024_entity_fixture.sql') ?? '',
        );

        self::assertEmpty($this->storage->get('202501011024_entity_skip.sql'));
        self::assertEmpty($this->storage->get('202501011024_skip.sql'));
    }
}
