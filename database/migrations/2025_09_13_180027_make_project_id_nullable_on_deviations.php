<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        DB::statement('ALTER TABLE deviations MODIFY project_id BIGINT UNSIGNED NULL');
    }
    public function down(): void
    {
        DB::statement('ALTER TABLE deviations MODIFY project_id BIGINT UNSIGNED NOT NULL');
    }
};
