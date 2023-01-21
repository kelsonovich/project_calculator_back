<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevisionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revision_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('action', \App\Models\RevisionLog::ENUM_ACTIONS);
            $table->string('revisionable_type');
            $table->string('revision_id');
            $table->string('model_id')->nullable();
            $table->string('key')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
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
        Schema::dropIfExists('revision_logs');
    }
}
