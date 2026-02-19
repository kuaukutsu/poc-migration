<?php

/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Setup;

final class FilesSetupTest extends TestCase
{
    public function testSimple(): void
    {
        $fs = new Setup(dirname(__DIR__) . '/migration/postgres/setup', 'test');
        self::assertTrue($fs->all()->valid());

        foreach ($fs->all() as $filename => $sql) {
            self::assertEquals('setup.sql', $filename);
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS "test"', $sql);
        }
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);

        $fs = new Setup(dirname(__DIR__) . '/migration/postgres/not-exists', 'test');
        $fs->all()->valid();
    }
}
