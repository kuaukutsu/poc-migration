<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Args;
use kuaukutsu\poc\migration\internal\filesystem\Action;

final class FilesDownTest extends TestCase
{
    private Action $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new Action(dirname(__DIR__) . '/migration/postgres/main');
    }

    public function testDown(): void
    {
        $savedFilenames = ['202501011024_entity_create.sql' => 1];

        $iterator = $this->fs->down($savedFilenames);
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_create.sql', $filename);
            self::assertStringContainsString('DROP TABLE', $sql);
        }
    }

    public function testLimit(): void
    {
        $savedFilenames = [
            '202501011024_entity_create.sql' => 1,
            '202501021024_account_create.sql' => 1,
            '202501021025_account_email.sql' => 1,
        ];

        $iterator = $this->fs->down($savedFilenames, new Args(limit: 1));
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

    public function testLimitSkipZero(): void
    {
        $savedFilenames = ['202501011024_entity_create.sql' => 1];

        $iterator = $this->fs->down($savedFilenames, new Args(limit: 0));
        self::assertTrue($iterator->valid());
        self::assertNotEmpty(iterator_count($iterator));
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
        $savedFilenames = ['not_match.sql' => 1];
        $this->expectException(ConfigurationException::class);

        $this->fs->down($savedFilenames)->valid();
    }

    public function testDirNotExists(): void
    {
        $savedFilenames = ['202501011024_entity_create.sql' => 1];
        $this->expectException(ConfigurationException::class);

        $fs = new Action(dirname(__DIR__) . '/migration/postgres/not-exists');
        $fs->down($savedFilenames)->valid();
    }
}
