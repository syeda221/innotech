<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add paid_amount and due_amount to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_net');
            }
            if (!Schema::hasColumn('sales', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            }
        });

        // Initialize paid_amount and due_amount for existing sales
        DB::statement("UPDATE sales SET 
            paid_amount = COALESCE(cash, 0) + COALESCE(card, 0),
            due_amount = GREATEST(0, COALESCE(total_net, 0) - (COALESCE(cash, 0) + COALESCE(card, 0)))
        ");

        // 2. Create voucher_invoice_allocations table
        if (!Schema::hasTable('voucher_invoice_allocations')) {
            Schema::create('voucher_invoice_allocations', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_type', 20); // 'receipt' or 'payment'
                $table->unsignedBigInteger('receipt_voucher_id')->nullable()->index();
                $table->unsignedBigInteger('payment_voucher_id')->nullable()->index();
                $table->unsignedBigInteger('voucher_master_id')->nullable()->index();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->unsignedBigInteger('purchase_id')->nullable()->index();
                $table->string('invoice_no', 100)->nullable();
                $table->date('invoice_date')->nullable();
                $table->decimal('invoice_amount', 15, 2)->default(0);
                $table->decimal('previous_due', 15, 2)->default(0);
                $table->decimal('allocated_amount', 15, 2)->default(0);
                $table->decimal('remaining_due', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_invoice_allocations');

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'due_amount')) {
                $table->dropColumn('due_amount');
            }
            if (Schema::hasColumn('sales', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
