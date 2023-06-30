<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasImportadasEntradaDevolucaoReferencia extends Model
{
    protected $fillable = [
        'chave_numero_nota', 
        'notas_importadas_entradas_id',
    ];

    public function notasEntradaImportadas(){
        return $this->hasOne('App\NotasImportadasEntrada', 'id', 'notas_importadas_entradas_id');
    }

    public function notasNasajonChave(){
        return $this->hasOne('App\NotasNasajon', 'chavene', 'chave_numero_nota');
    }

    public function notasNasajonNumero(){
        return $this->hasMany('App\NotasNasajon', 'numero', 'chave_numero_nota');
    }
}
