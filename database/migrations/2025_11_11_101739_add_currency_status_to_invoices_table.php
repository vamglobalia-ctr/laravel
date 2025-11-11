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
        Schema::table('invoices', function (Blueprint $table) {
            $table->tinyInteger('currency_status')
                  ->default(0)
                  ->comment('0 = USD, 1 = INR')
                  ->after('release_amount_inr'); // optional: adjust to the correct column position
        });
    }
 
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency_status');
        });
    }
};
