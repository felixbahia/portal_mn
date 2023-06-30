<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RenegociacaoTituloAvalista extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'renegociacao_titulos_id',
        'nome',
        'cpf',
        'email',
        'ip',
        'data_assinatura',
        'aceito',
        'endereco',
        'estado_civil',
        'nome_venia_conjugal',
        'cpf_venia_conjugal',
        'estabelecimento_codigo',
        'cpf_cnpj',
        'email_venia_conjugal',
        'ip_venia_conjugal',
        'data_assinatura_venia_conjugal',
        'aceito_venia_conjugal',
        'documento_assinado',
        'documento_assinado_conjuge',
        'tipo_signatario',
        'deleted_by',
        'created_by',
        'updated_by',

    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesRenegociacao(){
        return $this->hasOne('App\RenegociacaoTitulo', 'id', 'renegociacao_titulos_id');
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
