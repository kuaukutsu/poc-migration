<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\ActionFilesystem;

final class FilesDownTest extends TestCase
{
    private ActionFilesystem $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/main');
    }

    public function testDownFile(): void
    {
        $iterator = $this->fs->down(['202501011024_entity_create.sql']);
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_create.sql', $filename);
            self::assertStringContainsString('DROP TABLE', $sql);
        }
    }

    public function testFilterEmpty(): void
    {
        $listFilename = [];
        foreach ($this->fs->down([]) as $filename => $_) {
            $listFilename[] = $filename;
        }

        self::assertEmpty($listFilename);
    }

    public function testFilterNotMatch(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->fs->down(['not_match'])->valid();
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);

        $fs = new ActionFilesystem(dirname(__DIR__) . '/data/postgres/not-exists');
        $fs->down(['202501011024_entity_create.sql'])->valid();
    }
}
