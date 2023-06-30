<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PecaProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peca_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('produto');
            $table->string('peca');
            $table->string('codigo_barras');
            $table->unsignedInteger('endereco_empresa_id')->nullable();
            $table->foreign('endereco_empresa_id')
                ->references('id')
                ->on('endereco_empresas')
                ->onDelete('NO ACTION');
            $table->unsignedInteger('status_peca_id');
            $table->foreign('status_peca_id')
                ->references('id')
                ->on('status_pecas')
                ->onDelete('NO ACTION');
            $table->softDeletes();
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
        Schema::table('peca_produtos', function (Blueprint $table) {
            $table->dropForeign(['status_peca_id']);
        });
        Schema::dropIfExists('peca_produtos');
    }
}
