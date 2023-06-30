<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RenegociacaoTituloParcela extends Model
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
        'data_parcela',
        'numero',
        'valor',
        'created_by',
        'updated_by',
        'deleted_by',
        'titulo_id_nasajon',
        'retorno_nasajon',
        'titulo_excluido_renegociados_id',
        'parcela_sem_encargos',
        'dias_vencimento',
        'encargo_dia',
        'encargo_periodo',
        'encargo_ragazzi',
        'encargo_total',
        'parcela_sem_honorario',
        'encargo_dia_ragazzi',
        'encargo_periodo_ragazzi',
        'renegociacao_liberada'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function detalhesRenegociacao(){
        return $this->hasOne('App\RenegociacaoTitulo', 'id', 'renegociacao_titulos_id');
    }

    public function titulosEmAbertoNasajon(){
        return $this->hasOne('App\TitulosEmAbertoNasajon', 'titulo_id', 'titulo_id_nasajon');
    }
}
