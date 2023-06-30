<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ImportacaoFinanceiro extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','importacaos_id','data_embarque','debito_credito','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function importacaoDetalhes(){
    	return $this->hasOne('App\Importacao', 'id', 'importacaos_id');
    }

    public function lancamentos(){
    	return $this->hasMany('App\ImportacaoFinanceiroLancamento', 'importacao_financeiros_id', 'id')->orderBy('parcela');
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

    public function valorCambioTotal(){
        return $this->hasOne('App\ImportacaoFinanceiroLancamento', 'importacao_financeiros_id', 'id')
            ->select(DB::raw("SUM(real_valor) as total"))->whereNotIn('fechamento_tipo', ['imposto'])->where('previsto', false);
    }

    public function valorCambioDolarTotal(){
        return $this->hasOne('App\ImportacaoFinanceiroLancamento', 'importacao_financeiros_id', 'id')
            ->select(DB::raw("SUM(cambio_valor) as total"))->whereNotIn('fechamento_tipo', ['imposto'])->where('previsto', false);
    }

    public function previstos(){
    	return $this->hasMany('App\ImportacaoFinanceiroPrevisto', 'importacao_financeiros_id', 'id');
    }
}
