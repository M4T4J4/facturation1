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
        Schema::create('water_meters', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('status')->default('active')->index();
            $table->foreignId('subscription_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->date('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_meters');
    }
};
