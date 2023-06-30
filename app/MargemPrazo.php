<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MargemPrazo extends Model
{
    use SoftDeletes;

	protected $table = "margem_prazos";
	protected $fillable = ['estabelecimento', 'fator_diario', 'preco_a', 'preco_b', 'preco_c'];

	public function estabelecimento_detalhe(){
		$this->hasOne('App\Estabelecimento', 'ESTABEL', 'estabelecimento');
	}

}
