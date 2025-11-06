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
            $table->string('project_id', 100);
            $table->string('client_name', 100)->nullable();
            $table->string('tl', 100)->nullable();
            $table->text('invoice_link')->nullable();
            $table->date('invoice_sent_date')->nullable();
            $table->date('invoice_cycle_start')->nullable();
            $table->date('invoice_cycle_end')->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->string('invoice_status', 50)->nullable();
            $table->decimal('amount_usd', 12, 2)->nullable();
            $table->string('sent_via', 50)->nullable();
            $table->string('invoice_release_status', 50)->nullable();
            $table->date('followup_date')->nullable();
            $table->date('release_amount_date')->nullable();
            $table->decimal('release_amount_inr', 15, 2)->nullable();
            $table->timestamps(); 
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
