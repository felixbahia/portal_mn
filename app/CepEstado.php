<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CepEstado extends Model
{
    protected $connection = 'cep';
    protected $table = 'estado';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'uf';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uf', 'estado', 'cod_ibge', 'regiao'
    ];

    public function cidades(){
        return $this->hasMany("App\CepCidade", "uf", "uf")->select("id_cidade", "cidade", "uf");
    }

    public function aliquota_origem(){
        return $this->belongsTo('App\AliquotaPreco', "origem", 'uf');
    }

    public function aliquota_destino(){
        return $this->belongsTo('App\AliquotaPreco', "estado", 'uf');
    }
}
