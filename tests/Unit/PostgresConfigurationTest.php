<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PostgresConfigurationTest extends TestCase
{
    public function test_postgres_emulates_prepares_for_supabase_pooler_compatibility(): void
    {
        $options = Config::get('database.connections.pgsql.options', []);

        if (! extension_loaded('pdo_pgsql')) {
            $this->assertSame([], $options);
            return;
        }

        $this->assertTrue($options[\PDO::ATTR_EMULATE_PREPARES] ?? false);
    }
}
