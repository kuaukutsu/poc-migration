<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Action;

final class FilesRepeatableTest extends TestCase
{
    private Action $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new Action(dirname(__DIR__) . '/migration/postgres/main');
    }

    public function testRepeatableFile(): void
    {
        $iterator = $this->fs->repeatable();
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_correction.sql', $filename);
            self::assertStringContainsString('INSERT INTO', $sql);
            break;
        }
    }

    public function testOrderFile(): void
    {
        $iterator = $this->fs->repeatable();
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(2, $files);
        self::assertEquals('202501011024_entity_correction.sql', $files[0]);
        self::assertEquals('202501011124_account_correction.sql', $files[1]);
    }

    public function testSkipFile(): void
    {
        $listFilename = [];
        foreach ($this->fs->repeatable() as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011025_skip.sql', $listFilename);
    }

    public function testSkipSection(): void
    {
        $listFilename = [];
        foreach ($this->fs->repeatable() as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011026_entity_ignore.sql', $listFilename);
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);

        $fs = new Action(dirname(__DIR__) . '/migration/postgres/not-exists');
        $fs->repeatable()->valid();
    }
}
