<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoPrologos extends Model
{
	protected $table = 'movimentacao_prologos';
	public $timestamps = false;
	
	protected $fillable = [
		'estabelecimento', 'produto_codigo', 'data_movimentacao', 'numero_ducomento', 'cliente_codigo', 'tipo_operacao', 'cfop', 'quantidade_movimento', 'preco', 'preco_custo', 'aliquota_usada', 'novo_custo', 'novo_saldo'
	];

	protected $dates = [
		'data_movimentacao'
	];

}
