<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadeNegocio extends Model
{
    use SoftDeletes;
    protected $table = 'unidades_negocios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','unidade', 'users_id','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesUsuarioResponsavel(){
        return $this->hasOne('App\User', 'id', 'users_id')->withTrashed();
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

    public function metas(){
        return $this->hasMany('App\UnidadeNegocioMeta', 'unidades_negocios_id', 'id');
    }
}
