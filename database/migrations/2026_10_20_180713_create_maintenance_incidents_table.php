<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('water_network_asset_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind');
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_incidents');
    }
};
