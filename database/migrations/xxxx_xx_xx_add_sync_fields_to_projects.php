<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'bucket')) {
                $table->enum('bucket', ['prosjekter','montering','henting'])
                    ->default('prosjekter')
                    ->index();
            }
            if (!Schema::hasColumn('projects', 'external_id')) {
                $table->string('external_id')->nullable()->index();
            }
            // Optional, but recommended for dedupe
            $table->unique('external_id');
        });
    }
    public function down(): void {}
};

