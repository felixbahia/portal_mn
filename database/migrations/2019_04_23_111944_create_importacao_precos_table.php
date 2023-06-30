<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportacaoPrecosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_precos', function (Blueprint $table) {
            $table->string('codigo_produto');
            $table->string('grupo');
            $table->string('marca');
            $table->string('linha');
            $table->string('subgrupo');
            $table->string('descricao');
            $table->decimal('preco_real');
            $table->decimal('preco_dolar');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->boolean('status');
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo_produto');
            $table->unique('codigo_produto', 'deleted_by');

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
        Schema::dropIfExists('importacao_precos');
    }
}
