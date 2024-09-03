<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('tasks', function (Blueprint $table) {
            // add foreign key user_id (developer_id)
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // add foreign key project_id
            $table->foreignId('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
        
        Schema::table('projects', function (Blueprint $table) {
            // add foreign key user_id
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');

        });
        
        Schema::table('task_logs', function (Blueprint $table) {
            // add foreign key user_id (developer_id)
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');

            // add foreign key task_id
            $table->foreignId('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
