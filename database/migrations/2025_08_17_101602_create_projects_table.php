<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id(); // local autoincrement
            $table->unsignedBigInteger('external_id')->nullable()->index(); // ID from Mobile Worker (later)
            $table->string('title');
            $table->string('customer_name')->nullable();
            $table->string('address')->nullable();
            $table->string('status', 64)->nullable();
            $table->timestamp('updated_at_from_api')->nullable();
            $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
