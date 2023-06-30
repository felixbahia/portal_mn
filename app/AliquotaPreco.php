<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AliquotaPreco extends Model
{

	use SoftDeletes;

	protected $table = "aliquota_precos";
	protected $fillable = ['origem', 'estado', 'aliquota', 'internacional', 'icms_venda', 'icms_venda_cliente_isento', 'frete_adicional'];

	public function origem_detalhe(){
   		return $this->hasOne('App\CepEstado', 'uf', 'origem');
	}

	public function estado_detalhe(){
   		return $this->hasOne('App\CepEstado', 'uf', 'estado');
	}
	
}
