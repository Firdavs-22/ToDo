<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todo_step', function (Blueprint $table) {
            $table->id();
            $table->string('step_name');
            $table->unsignedBigInteger('todo_id');
            $table->boolean('completed')->default(false);
            $table->integer('status');

            $table->foreign('todo_id')->references('id')->on('todo_list')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_step');
    }
};
