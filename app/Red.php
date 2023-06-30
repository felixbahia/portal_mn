<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Red extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','numero_documento','valor','taxa_cambio', 'vencimento', 'created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['vencimento', 'deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function detalhesRedImportacao(){
        return $this->hasMany('App\RedsImportacaoFinanceiroLancamento', 'reds_id', 'id');
    }
}
