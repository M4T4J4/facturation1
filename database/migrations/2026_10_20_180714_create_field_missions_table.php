<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_missions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('water_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('planned');
            $table->date('scheduled_for');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_missions');
    }
};
