<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Action;
use kuaukutsu\poc\migration\internal\filesystem\Args;

final class FilesUpTest extends TestCase
{
    private Action $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new Action(dirname(__DIR__) . '/migration/postgres/main');
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

    public function testLimitFile(): void
    {
        $iterator = $this->fs->up([], new Args(limit: 1));
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(1, $files);
        self::assertEquals('202501011024_entity_create.sql', $files[0]);

        $iterator = $this->fs->up([], new Args(limit: 2));
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(2, $files);
        self::assertEquals('202501011024_entity_create.sql', $files[0]);
        self::assertEquals('202501021024_account_create.sql', $files[1]);
    }

    public function testOrderFile(): void
    {
        $iterator = $this->fs->up([]);
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(3, $files);
        self::assertEquals('202501011024_entity_create.sql', $files[0]);
        self::assertEquals('202501021024_account_create.sql', $files[1]);
        self::assertEquals('202501021025_account_email.sql', $files[2]);
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

        $manager = new Action(dirname(__DIR__) . '/migration/postgres/not-exists');
        $manager->up([])->valid();
    }
}
