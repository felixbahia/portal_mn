<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

 class InformacaoAdicionalProduto extends Model
{
    protected $connection = 'pgsql';

	protected $fillable = ['id', 'cod_produto', 'largura', 'gramatura', 'exibir_nacional', 'nao_exibir', 'linha_hospitalar', 'created_by', 'modified_by', 'valor_ultima_compra', 'data_ultima_compra', 'rendimento'];

	public function informacoes_adicionais(){
		return $this->belongsTo('App\Produto', 'cod_produto', 'CODPRD');
	}

	public function produtoNasajon(){
		return $this->belongsTo('App\ProdutoNasajon', 'cod_produto', 'codigo');
	}

}
