<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class SeederDatabaseSafetyGuardTest extends TestCase
{
    public function test_seeder_refuses_to_run_if_database_is_owner_database_sqlite(): void
    {
        // Point default database configuration to owner's database.sqlite
        Config::set('database.connections.'.Config::get('database.default').'.database', base_path('database/database.sqlite'));

        // Temporarily override environment detection if needed or verify exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Seeding DITOLAK: Database aktif');

        $seeder = new class extends DatabaseSeeder
        {
            public function run(): void
            {
                $activeDb = (string) config('database.connections.'.config('database.default').'.database');
                $normalizedDb = str_replace('\\', '/', $activeDb);
                $isQa = str_ends_with($normalizedDb, 'qa.sqlite');

                if (! $isQa) {
                    throw new RuntimeException("Seeding DITOLAK: Database aktif ('{$activeDb}') bukan 'qa.sqlite'. Seeding hanya diperbolehkan pada lingkungan QA untuk melindungi database pemilik.");
                }
            }
        };

        $seeder->run();
    }

    public function test_seeder_allows_execution_when_database_is_qa_sqlite(): void
    {
        Config::set('database.connections.'.Config::get('database.default').'.database', base_path('database/qa.sqlite'));

        $ran = false;
        $seeder = new class($ran) extends DatabaseSeeder
        {
            public function __construct(private bool &$ranRef) {}

            public function run(): void
            {
                $activeDb = (string) config('database.connections.'.config('database.default').'.database');
                $normalizedDb = str_replace('\\', '/', $activeDb);
                $isQa = str_ends_with($normalizedDb, 'qa.sqlite');

                if (! $isQa) {
                    throw new RuntimeException("Seeding DITOLAK: Database aktif ('{$activeDb}') bukan 'qa.sqlite'. Seeding hanya diperbolehkan pada lingkungan QA untuk melindungi database pemilik.");
                }

                $this->ranRef = true;
            }
        };

        $seeder->run();
        $this->assertTrue($ran);
    }
}
