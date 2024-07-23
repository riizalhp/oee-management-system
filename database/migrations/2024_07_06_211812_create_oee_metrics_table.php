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
        Schema::create('oee_metrics', function (Blueprint $table) {
            $table->id();
            $table->float('availability');
            $table->float('runtime');
            $table->float('downtime');
            $table->float('operating_time');
            $table->float('performance');
            $table->float('quality');
            $table->integer('reject')->default(0);
            $table->float('oee');
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
        Schema::dropIfExists('oee_metrics');
    }
};