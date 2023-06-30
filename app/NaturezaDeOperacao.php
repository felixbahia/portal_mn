<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class NaturezaDeOperacao extends Model
{
	use SoftDeletes;

	protected $table = "naturezas_de_operacao";
    protected $fillable = ['estabelecimento', 'estado_destino', 'nat_op_pj', 'nat_op_pf', 'nat_op_venda_conta_ordem', 'nat_op_remessa_conta_ordem'];

    public function tipo_prologos_pj(){
    	return $this->hasOne('App\TipoOperacao', 'TIPOPER', 'nat_op_pj');
    }

    public function tipo_prologos_pf(){
    	return $this->hasOne('App\TipoOperacao', 'TIPOPER', 'nat_op_pf');
    } 

    public function estado_detalhe(){
    	return $this->hasOne('App\CepEstado', 'uf', 'estado_destino');
    }
}
