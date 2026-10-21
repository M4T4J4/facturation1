<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_network_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('location');
            $table->string('status')->default('operational');
            $table->unsignedInteger('capacity')->nullable();
            $table->date('last_inspection_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_network_assets');
    }
};
