<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\ActionFilesystem;

final class FilesUpTest extends TestCase
{
    private ActionFilesystem $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/main');
    }

    public function testUpFile(): void
    {
        $iterator = $this->fs->up([]);
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_create.sql', $filename);
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
            break;
        }
    }

    public function testSkipFile(): void
    {
        $listFilename = [];
        foreach ($this->fs->up([]) as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011025_skip.sql', $listFilename);
    }

    public function testSkipSection(): void
    {
        $listFilename = [];
        foreach ($this->fs->up([]) as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011026_entity_duplicate.sql', $listFilename);
    }

    public function testFilter(): void
    {
        $savedFilenames = ['202501011024_entity_create.sql'];

        $listFilename = [];
        foreach ($this->fs->up($savedFilenames) as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011024_entity_create.sql', $listFilename);
    }

    public function testFilterNotMatch(): void
    {
        $savedFilenames = ['202501011024_not_match_filename.sql'];

        $listFilename = [];
        foreach ($this->fs->up($savedFilenames) as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertContains('202501011024_entity_create.sql', $listFilename);
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);

        $manager = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/not-exists');
        $manager->up([])->valid();
    }
}
