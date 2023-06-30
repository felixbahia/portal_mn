<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAliquotaPrecosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aliquota_precos', function (Blueprint $table) {
            $table->increments('id');

            $table->char('origem');
            $table->char('estado');
            $table->float('aliquota');
            $table->boolean('internacional');

            $table->integer('created_by');
            $table->integer('modified_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aliquota_precos');
    }
}
