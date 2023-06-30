<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemessaConsignacaosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remessa_consignacaos', function (Blueprint $table) {
            $table->increments('id');
			$table->unsignedInteger('pedido_id')->nullable();
			$table->string('arquivo');
			$table->integer('created_by');
            $table->timestamps();

			$table->foreign('pedido_id')
				->references('id')
				->on('pedido')
                ->onDelete('NO ACTION');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remessa_consignacaos');
    }
}
