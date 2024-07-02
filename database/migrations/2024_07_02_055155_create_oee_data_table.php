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
        Schema::create('oee_data', function (Blueprint $table) {
            $table->id();
            $table->integer('line');
            $table->string('nama_line');
            $table->date('tgl');
            $table->integer('shift');
            $table->string('item');
            $table->integer('seq');
            $table->timestamp('timestamp');
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
        Schema::dropIfExists('oee_data');
    }
};