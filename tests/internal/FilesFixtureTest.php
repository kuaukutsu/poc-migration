<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\ActionFilesystem;

final class FilesFixtureTest extends TestCase
{
    private ActionFilesystem $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/main');
    }

    public function testFixtureFile(): void
    {
        $iterator = $this->fs->fixture();
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_base.sql', $filename);
            self::assertStringContainsString('INSERT INTO', $sql);
            break;
        }
    }

    public function testSkipFile(): void
    {
        $listFilename = [];
        foreach ($this->fs->fixture() as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011025_skip.sql', $listFilename);
    }

    public function testSkipSection(): void
    {
        $listFilename = [];
        foreach ($this->fs->fixture() as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertNotContains('202501011026_entity_ignore.sql', $listFilename);
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);

        $fs = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/not-exists');
        $fs->fixture()->valid();
    }
}
