<?php

declare(strict_types=1);

namespace App\Console\Support;

use Illuminate\Console\{Command};

final class TenantCommandReport {
    private const CHUNK_SIZE = 50;

    private array $rows = [];

    public function __construct(
        private readonly Command $command,
        private readonly array $headers
    ) {
    }

    public function add(array $row): void {

        $this->rows[] = $row;

        if(count($this->rows) >= self::CHUNK_SIZE) {

            $this->flush();

        }

    }

    public function flush(): void {

        if($this->rows === []) {

            return;

        }

        $this->command->table($this->headers, $this->rows);

        $this->rows = [];

    }
}
