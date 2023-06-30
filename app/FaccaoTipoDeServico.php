<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaccaoTipoDeServico extends Model
{
    protected $table = 'faccao_x_tipo_de_servicos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','codigo_faccao','codigo_tipo_de_servico','preco','codigo_unidade','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
    ];

    function faccao(){
    	return $this->hasOne('App\Faccao', 'id', 'codigo_faccao');
    }

    function tipo_de_servico(){
    	return $this->hasOne('App\TipoDeServico', 'id', 'codigo_tipo_de_servico');
    }

    function unidade(){
        return $this->hasOne('App\UnidadeMedidaNasajon', 'codigo', 'codigo_unidade');
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
