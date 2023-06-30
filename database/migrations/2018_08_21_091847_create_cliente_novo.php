<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClienteNovo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cliente_novos', function (Blueprint $table) {
            $table->increments("id");
            $table->integer("status");
            $table->string("ja_foi_cliente", 3);
            $table->string("ja_foi_cliente_quando", 20)->nullable();
            $table->string("fisica_juridica", 1);
            $table->string("cpf_cnpj", 18);
            $table->string("inscricao_estadual", 100)->nullable();
            $table->string("inscricao_municipal", 100)->nullable();
            $table->string("nome_razao", 150)->nullable();
            $table->string("guerra_apelido", 100)->nullable();
            $table->string("telefone", 50)->nullable();
            $table->string("telefone_fax", 50)->nullable();
            $table->string("email", 100)->nullable();
            $table->string("vendedor_codigo", 10)->nullable();
            $table->string("transportador_codigo", 10)->nullable();
            $table->string("forte_cliente", 15)->nullable();
            $table->string("ramo_atividade", 15)->nullable();
            $table->string("numero_filiais", 10)->nullable();
            $table->string("numero_empregados", 10)->nullable();
            $table->string("predio_proprio", 3)->nullable();
            $table->string("aluguel", 10)->nullable();
            $table->string("sucessora_de", 50)->nullable();
            $table->string("ligacao_com", 50)->nullable();
            $table->string("sugestao_credito", 50)->nullable();
            $table->string("historico_cliente_praca", 50)->nullable();
            $table->string("faturamento_cep", 10)->nullable();
            $table->string("faturamento_logradouro", 150)->nullable();
            $table->string("faturamento_numero", 10)->nullable();
            $table->string("faturamento_complemento", 25)->nullable();
            $table->string("faturamento_bairro", 150)->nullable();
            $table->string("faturamento_cidade", 150)->nullable();
            $table->string("faturamento_estado", 2)->nullable();
            $table->string("faturamento_telefone", 50)->nullable();
            $table->string("faturamento_telefone_fax", 50)->nullable();
            $table->string("faturamento_email", 100)->nullable();
            $table->string("cobranca_cep", 10)->nullable();
            $table->string("cobranca_logradouro", 150)->nullable();
            $table->string("cobranca_numero", 10)->nullable();
            $table->string("cobranca_complemento", 25)->nullable();
            $table->string("cobranca_bairro", 150)->nullable();
            $table->string("cobranca_cidade", 150)->nullable();
            $table->string("cobranca_estado", 2)->nullable();
            $table->string("cobranca_telefone", 50)->nullable();
            $table->string("cobranca_telefone_fax", 50)->nullable();
            $table->string("cobranca_banco", 10)->nullable();
            $table->string("cobranca_agencia", 10)->nullable();
            $table->string("cobranca_conta", 10)->nullable();
            $table->float("limite_credito", 18, 2)->nullable();
            $table->string("motivo_recusa", 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
        });
        Schema::create('cliente_novo_socios', function (Blueprint $table) {
            $table->increments("id");
            $table->integer("cliente_novo_id");
            $table->string("nome", 100);
            $table->string("cpf", 18)->nullable();
            $table->string("parte", 10)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
        });
        Schema::create('cliente_novo_referencias', function (Blueprint $table) {
            $table->increments("id");
            $table->integer("cliente_novo_id");
            $table->string("empresa", 100);
            $table->string("contato", 100);
            $table->string("telefone_ddd", 3);
            $table->string("telefone", 100);
            $table->string("estado", 2);
            $table->string("cidade", 150);
            $table->softDeletes();
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cliente_novos');
        Schema::drop('cliente_novo_socios');
        Schema::drop('cliente_novo_referencias');
    }
}
