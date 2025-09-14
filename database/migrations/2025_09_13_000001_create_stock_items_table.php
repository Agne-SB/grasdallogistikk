<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('supplier');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('status', 20)->default('bestilt');
            $table->timestamp('delivered_at')->nullable();
            $table->text('issue_note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('delivery_date');
            $table->index('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
