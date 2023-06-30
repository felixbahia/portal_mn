<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasImportadasEntradaRelacaoNota extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'notas_importadas_entradas_id_cte',
        'notas_importadas_entradas_id_nfe',
        'chave'
    ];

    public function notasEntradaImportadas(){
        return $this->hasMany('App\NotasImportadasEntrada', 'id', 'notas_importadas_entradas_id_cte');
    }

    public function faturamentoOnline(){
        return $this->hasOne('App\FaturamentoOnline', 'nota_uuid', 'notas_importadas_entradas_id_nfe');
    }

    public function NotasEntradasNasajon(){
        return $this->hasMany('App\NotasEntradasNasajon', 'Chave NE', 'chave');
    }
}
