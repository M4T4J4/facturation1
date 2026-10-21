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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meter_reading_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('period');
            $table->unsignedInteger('consumption_m3')->default(0);
            $table->unsignedInteger('price_per_m3');
            $table->unsignedInteger('monthly_fee')->default(0);
            $table->unsignedBigInteger('amount');
            $table->date('due_at')->nullable();
            $table->string('status')->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
