<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransportadorNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_transportadoras';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'codigo', 'nome', 'nomefantasia', 'cnpj', 'cidade', 'estado', 'via_transporte', 'inscricaoestadual', 'email', 'cep', 'numero', 'endereco', 'bairro', 'fax', 'telefone'
    ];

    public function notas(){
        return $this->hasMany('App\NotasNasajon', 'id_transportadora', 'id');
    }

    public function transportadoraEstabelecimento(){
        return $this->hasOne('App\TransportadoraEstabelecimento', 'transportadora_codigo', 'codigo');
    }
    
}
