<?php

declare(strict_types=1);

namespace internal;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\filesystem\Action;

final class FilesCreateTest extends TestCase
{
    public function testCreate(): void
    {
        $fs = new Action(dirname(__DIR__) . '/migration/postgres/main/   ');

        $filepath = $fs->create('test.sql', 'body');
        self::assertEquals('test.sql', basename($filepath));
    }

    public function testDirNotExists(): void
    {
        $fs = new Action(dirname(__DIR__) . '/migration/postgres/not-exists');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/^the dir .+ is not exists.$/i');

        $fs->create('test.sql', 'body');
    }

    public function testConfigurationException(): void
    {
        $fs = new Action(dirname(__DIR__) . '/migration/postgres/main');
        $fs->create('test.sql', 'body');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/^the file .+ is exists.$/i');

        $fs->create('test.sql', 'body');
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $pattern = dirname(__DIR__) . '/migration/postgres/main/*test.sql';
        foreach (glob($pattern) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
