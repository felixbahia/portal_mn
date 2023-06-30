<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class NaturezaDeOperacaoNasajon extends Model
{
	use SoftDeletes;

	protected $table = "naturezas_de_operacao_nasajon";
    protected $fillable = ['estabelecimento', 'estado_destino', 'nat_op_pj', 'nat_op_pf', 'nat_op_venda_conta_ordem'];

    public function estado_detalhe(){
    	return $this->hasOne('App\CepEstado', 'uf', 'estado_destino');
    }
}
