<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('amount',8,3);
            $table->integer('term');
            $table->enum('status',['pending','approved','disapproved','paid'])->default('pending');
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('approved_by_id')->references('id')->on('admins')->onUpdate('cascade')->onDelete('cascade');
            $table->index(['customer_id', 'approved_by_id', 'created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
}
