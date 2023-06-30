<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasNaoProcessadasNasajon extends Model
{

    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_notas_nao_processadas';

    protected $guarded = [
        'estabelecimento_codigo',
        'nota_numero',
        'nota_emissao',
        'nota_valor',
        'nota_operacao'
    ];

    function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', ['numero', 'estabelecimento_codigo', 'operacao_codigo'], ['nota_numero', 'estabelecimento_codigo', 'nota_operacao']);
    }
}
