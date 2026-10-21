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
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('usage_type', 24)->index();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->unsignedInteger('fixed_fee')->default(0);
            $table->unsignedTinyInteger('tax_percent')->default(0);
            $table->timestamps();

            $table->unique(['usage_type', 'effective_from']);
            $table->index(['usage_type', 'effective_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
