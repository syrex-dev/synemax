<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DestructiveCommandsProhibitedTest extends TestCase
{
    public function test_destructive_commands_are_prohibited_when_configured_for_owner_database(): void
    {
        // Explicitly set prohibition to true as it is for non-qa databases
        DB::prohibitDestructiveCommands(true);

        try {
            Artisan::call('db:wipe', ['--force' => true]);
            $this->fail('Artisan db:wipe should have been prohibited.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('prohibited', strtolower($e->getMessage()));
        } finally {
            // Restore for testing environment
            DB::prohibitDestructiveCommands(false);
        }
    }

    public function test_migrate_fresh_is_prohibited_when_configured_for_owner_database(): void
    {
        DB::prohibitDestructiveCommands(true);

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->fail('Artisan migrate:fresh should have been prohibited.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('prohibited', strtolower($e->getMessage()));
        } finally {
            DB::prohibitDestructiveCommands(false);
        }
    }
}
