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
        Schema::create('machine_start_times', function (Blueprint $table) {
            $table->id();
            $table->string('line');
            $table->string('linedesc');
            $table->string('tipe_barang');
            $table->date('tanggal');
            $table->integer('shift');
            $table->dateTime('start_prod');
            $table->dateTime('finish_prod');
            $table->integer('worktime');
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
        Schema::dropIfExists('machine_start');
    }
};