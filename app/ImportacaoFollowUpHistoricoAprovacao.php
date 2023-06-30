<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoFollowUpHistoricoAprovacao extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','importacao_follow_up_id','tipo', 'aprovado', 'data','created_by','updated_by','deleted_by', 'produto_codigo'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['data', 'created_at', 'updated_at', 'deleted_at'];

    public function importacaoFollowUpDetalhes(){
    	return $this->hasOne('App\ImportacaoFollowUp', 'id', 'importacao_follow_up_id');
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
