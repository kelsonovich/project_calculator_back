<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable();
            $table->integer('project_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort')->default(100);
            $table->float('analyst_hours')->nullable();
            $table->float('designer_hours_min')->nullable();
            $table->float('designer_hours_max')->nullable();
            $table->float('front_hours_min')->nullable();
            $table->float('front_hours_max')->nullable();
            $table->float('back_hours_min')->nullable();
            $table->float('back_hours_max')->nullable();
            $table->string('revision_id')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('tasks');
    }
}
