<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Action;
use kuaukutsu\poc\migration\internal\filesystem\Options;

final class FilesFixtureTest extends TestCase
{
    private Action $fs;

    #[Override]
    protected function setUp(): void
    {
        $this->fs = new Action(dirname(__DIR__) . '/migration/postgres/main/   ');
    }

    public function testFixture(): void
    {
        $iterator = $this->fs->fixture();
        self::assertTrue($iterator->valid());

        foreach ($iterator as $filename => $sql) {
            self::assertEquals('202501011024_entity_base.sql', $filename);
            self::assertStringContainsString('INSERT INTO', $sql);
            break;
        }
    }

    public function testLimit(): void
    {
        $iterator = $this->fs->fixture(new Options(limit: 1));
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(1, $files);
        self::assertEquals('202501011024_entity_base.sql', $files[0]);

        $iterator = $this->fs->fixture(new Options(limit: 2));
        self::assertTrue($iterator->valid());

        $files = [];
        foreach ($iterator as $filename => $_) {
            $files[] = $filename;
        }

        self::assertCount(2, $files);
        self::assertEquals('202501011024_entity_base.sql', $files[0]);
        self::assertEquals('202501011034_entity_correction.sql', $files[1]);
    }

    public function testLimitSkipZero(): void
    {
        $iterator = $this->fs->fixture(new Options(limit: 0));
        self::assertTrue($iterator->valid());
        self::assertNotEmpty(iterator_count($iterator));
    }

    public function testSkip(): void
    {
        $listFilename = [];
        foreach ($this->fs->fixture() as $filename => $_) {
            $listFilename[] = $filename;
        }

        // filename
        self::assertNotContains('202501011025_skip.sql', $listFilename);
        // section
        self::assertNotContains('202501011026_entity_ignore.sql', $listFilename);
    }

    public function testDirNotExists(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/^the directory .+ does not exist.$/i');

        $fs = new Action(dirname(__DIR__) . '/migration/postgres/not-exists');
        $fs->fixture()->valid();
    }
}
