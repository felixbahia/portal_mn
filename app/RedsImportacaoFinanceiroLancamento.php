<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RedsImportacaoFinanceiroLancamento extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','reds_id','importacao_financeiro_lancamentos_id','utilizado_valor', 'created_by','updated_by','deleted_by'
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

    public function detalhesRed(){
        return $this->hasOne('App\Red', 'id', 'reds_id');
    }

    public function detalhesLancamentos(){
        return $this->hasOne('App\ImportacaoFinanceiroLancamento', 'id', 'importacao_financeiro_lancamentos_id');
    }
}
