<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('deviations', function (Blueprint $t) {
        $t->id();
        $t->foreignId('project_id')->constrained()->cascadeOnDelete();
        $t->string('source')->index(); // 'henting' | 'montering'
        $t->string('type')->index();   // 'mangler' | 'skade' | 'feil vare' | 'annet'
        $t->text('note')->nullable();
        $t->integer('qty_expected')->nullable();
        $t->integer('qty_received')->nullable();
        $t->string('status')->default('open')->index(); // 'open' | 'resolved'
        $t->timestamp('opened_at')->nullable();
        $t->timestamp('resolved_at')->nullable();
        $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('deviations');
    }
};
