<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('projects', function (Blueprint $t) {
        if (!Schema::hasColumn('projects','external_number'))   $t->string('external_number')->nullable()->index(); // OrderKey
        if (!Schema::hasColumn('projects','supervisor_name'))   $t->string('supervisor_name')->nullable()->index();
        if (!Schema::hasColumn('projects','supervisor_email'))  $t->string('supervisor_email')->nullable();
        if (!Schema::hasColumn('projects','supervisor_phone'))  $t->string('supervisor_phone')->nullable();
        if (!Schema::hasColumn('projects','vendor_status'))     $t->string('vendor_status')->nullable()->index();
        if (!Schema::hasColumn('projects','vendor_updated_at')) $t->timestamp('vendor_updated_at')->nullable()->index();
        if (!Schema::hasColumn('projects','vendor_closed_at'))  $t->timestamp('vendor_closed_at')->nullable()->index();
        });
    }
    public function down(): void {}
};
