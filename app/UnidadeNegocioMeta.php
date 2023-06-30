<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadeNegocioMeta extends Model
{
    use SoftDeletes;
    protected $table = 'unidade_negocio_metas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'unidades_negocios_id', 'data', 'valor', 'created_by', 'updated_by', 'deleted_by'
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

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
    
    public function usuarios(){
        return $this->hasMany('App\UnidadeNegocioMetaXUser', 'unidade_negocio_metas_id', 'id');
    }
}
