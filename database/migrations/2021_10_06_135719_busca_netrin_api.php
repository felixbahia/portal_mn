<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuscaNetrinApi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netrin_apis', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tipo');
            $table->string('url');
            $table->text('access_token');
            $table->string('cpf_cnpj');
            $table->string('uf')->nullable();
            $table->string('servico')->nullable();
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


        });
        Schema::create('netrin_api_retornos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_netrin_api');
            $table->jsonb('json_retorno')->nullable();
            $table->boolean('alerta_erro')->default(false);
            $table->integer('status_code');
            $table->string('mensagem');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('id_netrin_api')
            ->references('id')
            ->on('netrin_apis')
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
        Schema::dropIfExists('netrin_api_retornos');
        Schema::dropIfExists('netrin_apis');
    }
}
