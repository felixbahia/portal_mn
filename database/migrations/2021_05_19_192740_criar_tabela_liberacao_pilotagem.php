<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaLiberacaoPilotagem extends Migration
{
   /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('liberacao_pilotagems', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lancamentos_deb_cred_vendedor_id')->nullable();
            $table->string('estabelecimento');
            $table->integer('users_id');
            $table->string('nota');
            $table->uuid('nota_id');
            $table->float('valor_credito')->nullable();
            $table->float('valor_desconto')->nullable();
            $table->integer('parcela')->nullable();
            $table->float('comissao_atual')->nullable();
            $table->float('comissao_aterada')->nullable();
            $table->integer('motivos_financeiros_id');
            $table->integer('status_liberacao_pilotagems_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status_liberacao_pilotagems_id')
                ->references('id')
                ->on('status_liberacao_pilotagems')
                ->onDelete('NO ACTION');
            $table->foreign('users_id')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('lancamentos_deb_cred_vendedor_id')
                ->references('id')
                ->on('lancamentos_deb_cred_vendedor')
                ->onDelete('NO ACTION');
            $table->foreign('motivos_financeiros_id')
                ->references('id')
                ->on('motivos_financeiros')
                ->onDelete('NO ACTION');
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
        Schema::dropIfExists('liberacao_pilotagems');
    }
}
