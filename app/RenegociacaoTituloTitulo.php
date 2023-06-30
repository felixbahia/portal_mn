<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RenegociacaoTituloTitulo extends Model
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
        'titulo_uuid_nasajon',
        'titulo_numero',
        'created_by',
        'updated_by',
        'deleted_by',
        'titulo_valor',
        'estabelecimento_codigo',
        'cpf_cnpj',
        'dias_vencido',
        'encargo_dia',
        'encargo_periodo',
        'encargo_total',
        'valor_total'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesTituloAberto(){
        return $this->hasOne('App\TitulosEmAbertoNasajon', 'titulo_id','titulo_uuid_nasajon');
    }

    public function detalhesTitulosPagos(){
        return $this->hasOne('App\ContasReceberBaixadoNasajon', 'id_titulo', 'titulo_uuid_nasajon');
    }

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
