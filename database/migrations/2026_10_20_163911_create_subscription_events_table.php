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
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('previous_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_user_name')->nullable();
            $table->string('new_user_name')->nullable();
            $table->string('old_user_email')->nullable();
            $table->string('new_user_email')->nullable();
            $table->string('old_occupant_name')->nullable();
            $table->string('new_occupant_name')->nullable();
            $table->string('old_occupant_phone', 30)->nullable();
            $table->string('new_occupant_phone', 30)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('event_at')->index();
            $table->timestamps();

            $table->index(['subscription_id', 'event_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
    }
};
