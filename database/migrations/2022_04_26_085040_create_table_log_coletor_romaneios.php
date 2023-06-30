<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLogColetorRomaneios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_coletor_romaneios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('log_coletor_id')->nullable();
            $table->integer('produto_defeito_id')->nullable();
            $table->string('estabelecimento',2);
            $table->string('numero_nota',100);
            $table->timestamp('data_nota')->nullable(true);
            $table->string('cnpj_cpf',60)->nullable();;
            $table->string('fornecedor',255);
            $table->timestamp('data_coletor')->nullable(true);
            $table->string('produto_codigo',100);
            $table->string('produto_grupo',100)->nullable();
            $table->integer('peca_coletor');
            $table->float('quantidade_coletor');
            $table->integer('peca_romaneio');
            $table->float('quantidade_romaneio');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
    
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

            $table->foreign('log_coletor_id')
            ->references('id')
            ->on('log_coletors')
            ->onDelete('NO ACTION');
        $table->foreign('produto_defeito_id')
            ->references('id')
            ->on('produto_defeitos')
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
        Schema::dropIfExists('log_coletor_romaneios');
    }
}
