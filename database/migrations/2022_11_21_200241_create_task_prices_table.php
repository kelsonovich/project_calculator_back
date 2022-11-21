<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
            $table->float('analyst');
            $table->float('design_min')->default(0);
            $table->float('design_max')->default(0);
            $table->float('front_min')->default(0);
            $table->float('front_max')->default(0);
            $table->float('back_min')->default(0);
            $table->float('back_max')->default(0);
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
        Schema::dropIfExists('task_prices');
    }
}
