<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StatusClienteNovo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_cliente_novos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status');
            $table->boolean('exibe')->default(true);
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable(true);
            $table->unsignedInteger('deleted_by')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
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
        Schema::dropIfExists('status_clientes_novos');
    }
}
