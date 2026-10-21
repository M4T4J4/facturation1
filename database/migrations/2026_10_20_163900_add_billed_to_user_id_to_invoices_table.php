<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('billed_to_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('invoices')->update([
            'billed_to_user_id' => DB::raw('(select user_id from subscriptions where subscriptions.id = invoices.subscription_id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billed_to_user_id');
        });
    }
};
