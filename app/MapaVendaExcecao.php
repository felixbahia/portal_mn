<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MapaVendaExcecao extends Model
{
    use SoftDeletes;
    protected $table = 'mapa_venda_excecoes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'estabelecimento_codigo', 'numero_nota', 'unidades_negocios_id', 'data_emissao', 'created_by', 'updated_by', 'deleted_by'];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesUnidadeNegocio(){
        return $this->hasOne('App\UnidadeNegocio', 'id', 'unidades_negocios_id');
    }

    public function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', 'numero', 'numero_nota');
    }
}
