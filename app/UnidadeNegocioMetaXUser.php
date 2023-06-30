<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadeNegocioMetaXUser extends Model
{
    use SoftDeletes;
    protected $table = 'unidade_negocio_metas_x_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    //protected $table = 'laudos';

    protected $fillable = [
        'id', 'unidade_negocio_metas_id', 'users_id', 'created_by', 'updated_by', 'deleted_by', 'metas'
    ];
        /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];


    public function detalhesUsuario(){
        return $this->hasOne('App\User', 'id','users_id')->withTrashed();
    }

    public function detalhesUnidadeNegocioMeta(){
        return $this->hasOne('App\UnidadeNegocioMeta', 'id','unidade_negocio_metas_id');
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
}
