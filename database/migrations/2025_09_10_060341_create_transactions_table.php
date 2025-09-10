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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
             // Core transaction info
            $table->string('currency', 10);
            $table->string('customer_name')->nullable();
            $table->string('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('lang_key', 5)->default('en');

            // Merchant + Order references
            $table->string('merchant_reference_id')->unique();
            $table->string('order_transaction_id')->nullable(); // Enkap orderTransactionId
            // $table->string('optRefOne')->nullable();
            // $table->string('optRefTwo')->nullable();

            // Dates
            $table->timestamp('expiry_date')->nullable();
            $table->timestamp('order_date')->nullable();

            // Amount
            $table->decimal('total_amount', 15, 2);

            // Nested objects stored as JSON
            // $table->json('id')->nullable();      // { uuid, version }
            $table->json('items')->nullable();   // product/item list
            $table->string('redirect_url')->nullable();

            // Status tracking
            $table->string('status')->default('CREATED');
            $table->string('payment_provider')->nullable(); // future support for PayPal, Card, etc

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
