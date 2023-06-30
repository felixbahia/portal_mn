<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContaContabilSaldo extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'conta_classificacao', 'conta', 'conta_nome', 'movimentacao_antes_do_encerramento', 'movimentacao', 'saldo_antes_do_encerramento', 'saldo', 'ano_mes', 'ano', 'mes', 'data', 'empresa', 'empresa_razao_social', 'empresa_cnpj', 'estabelecimento_codigo', 'estabelecimento_nome', 'estabelecimento_cnpj', 'nivel', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['data', 'created_at', 'updated_at', 'deleted_at'];

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
