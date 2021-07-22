<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('address');
            $table->decimal('total_amount', 10 ,2);
            $table->text('remark')->nullable();
            $table->boolean('paid')->default(false);
            $table->dateTime('paid_at')->nullable();
            $table->unsignedMediumInteger('payment_method')->nullable();
            $table->string('payment_no')->nullable();
            $table->unsignedMediumInteger('refund_status')->default(\App\Models\Order\Order::REFUND_STATUS_PENDING);
            $table->string('refund_no')->nullable()->unique();
            $table->boolean('closed')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->unsignedMediumInteger('ship_status')->default(\App\Models\Order\Order::SHIP_STATUS_PENDING);
            $table->text('ship_data')->nullable();
            $table->text('extra')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
