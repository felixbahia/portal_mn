<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacaoCusto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_custos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('importacaos_id');
            $table->string('ii')->nullable();
            $table->string('ipi')->nullable();
            $table->string('pis')->nullable();
            $table->string('cofins')->nullable();
            $table->string('afrmm')->nullable();
            $table->float('taxa_siscomex')->nullable();
            $table->float('sda')->nullable();
            $table->float('honorarios')->nullable();
            $table->float('expediente')->nullable();
            $table->float('valor_li')->nullable();
            $table->float('agencia_maritima')->nullable();
            $table->float('armazem')->nullable();
            $table->float('laudo')->nullable();
            $table->float('outras_despesas')->nullable();
            $table->string('icms_saida')->nullable();
            $table->float('seguro')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('importacaos_id')
                ->references('id')
                ->on('importacaos')
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
        Schema::dropIfExists('importacao_custos');
    }
}
