<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('projects', function (Blueprint $t) {
        if (!Schema::hasColumn('projects','supplier_eta'))        $t->date('supplier_eta')->nullable()->index();
        if (!Schema::hasColumn('projects','delivered_at'))        $t->timestamp('delivered_at')->nullable()->index();
        if (!Schema::hasColumn('projects','staged_location'))     $t->string('staged_location')->nullable();
        if (!Schema::hasColumn('projects','ready_at'))            $t->timestamp('ready_at')->nullable()->index();
        if (!Schema::hasColumn('projects','notified_at'))         $t->timestamp('notified_at')->nullable()->index();
        if (!Schema::hasColumn('projects','pickup_time_from'))    $t->timestamp('pickup_time_from')->nullable()->index();
        if (!Schema::hasColumn('projects','pickup_time_to'))      $t->timestamp('pickup_time_to')->nullable()->index();
        if (!Schema::hasColumn('projects','appointment_notes'))   $t->string('appointment_notes')->nullable();
        if (!Schema::hasColumn('projects','pickup_collected_at')) $t->timestamp('pickup_collected_at')->nullable()->index();
        if (!Schema::hasColumn('projects','requires_appointment'))$t->boolean('requires_appointment')->default(false)->index();
        });
    }
    public function down(): void {}
};
