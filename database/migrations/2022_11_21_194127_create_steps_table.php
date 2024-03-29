<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable();
            $table->string('project_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('code')->nullable();
            $table->integer('employee_quantity')->default(1);
            $table->integer('agreement')->default(0);
            $table->integer('parallels')->default(0);
            $table->integer('sort')->default(100);
            $table->boolean('isClient')->default(false);
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
        Schema::dropIfExists('steps');
    }
}
