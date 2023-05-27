<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->date('payment_due_date');
            $table->decimal('due_amount',8,5);
            $table->decimal('paid_amount',8,5)->default(0);
            $table->enum('status',['pending','paid'])->default('pending');
            $table->timestamps();
            $table->foreign('loan_id')->references('id')->on('loans');
            $table->index(['loan_id','created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_payments');
    }
}
