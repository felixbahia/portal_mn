<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoFinanceiroLancamento extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','importacao_financeiros_id','cambio_data','cambio_valor','real_taxa','real_valor','banco','fechamento_tipo','cambio_numero_contrato','banco_numero_contrato','modalidade','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'cambio_data'];

    public function importacaoFinanceiroDetalhes(){
    	return $this->hasOne('App\ImportacaoFinanceiro', 'id', 'importacao_financeiros_id');
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

    public function redDetalhes(){
    	return $this->hasOne('App\Red', 'id', 'reds_id');
    }

    public function detalhesRedsUtilizado(){
        return $this->hasMany('App\RedsImportacaoFinanceiroLancamento', 'importacao_financeiro_lancamentos_id', 'id');
    }

    public function detalhesLancamenttoPago(){
        return $this->hasMany('App\ImportacaoFinanceiroLancamento', 'id', 'importacao_financeiro_lancamentos_id_baixa');
    }
}
