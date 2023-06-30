<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnidadeMedidaNasajon extends Model
{

	protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_unidades';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'unidade';
	protected $keyType = 'string';

	public $fillable = [
		'codigo',
		'descricao',
		'unidade',
	];

	public function produto(){
		return $this->belongsToMany('App/ProdutoNasajon', 'produto_unidadedemedida', 'unidade');
	}

}
