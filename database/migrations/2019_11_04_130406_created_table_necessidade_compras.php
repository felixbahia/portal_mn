<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableNecessidadeCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('necessidades_compras', function (Blueprint $table) {
            $table->increments('id');
            $table->string('produto_codigo', 60);
            $table->string('fornecedor_cnpj_cpf')->nullable();
            $table->float('quantidade');
            $table->float('quantidade_enviada')->nullable();
            $table->float('saldo');
            $table->string('pedido_compra_numero')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

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
        Schema::dropIfExists('necessidades_compras');
    }
}
