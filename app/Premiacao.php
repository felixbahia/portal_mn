<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Premiacao extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','data_meta','codigo_vendedor','users_id','unidades_negocios_id','meta_valor','movimentacao_valor','titulo_valor','comissao'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesUnidadeNegocio(){
        return $this->hasOne('App\UnidadeNegocio', 'id', 'unidades_negocios_id');
    }

    public function detalhesUsuario(){
        return $this->hasOne('App\User', 'id','users_id')->withTrashed();
    }

    public function lancamentoDebCredVendedor(){
        return $this->hasMany('App\LancamentoDebCredVendedor', 'codigo_vendedor','users_id');
    }
}
