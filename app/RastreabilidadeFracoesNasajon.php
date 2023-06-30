<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RastreabilidadeFracoesNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_rastreabilidade_fracoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'fracao';

    protected $fillable = [
        'fracao', 
        'codigo', 
        'data_hora_criacao', 
        'data', 
        'acao', 
        'proprietario',
        'detentor',  
        'produto',
        'quantidade',
        'endereco',  
        'nome',
        'ra', 
        'documento'
    ];

    public function detalhes_produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto');
    }

    public function notaSaida(){
        return $this->hasOne('App\NotasNasajon', ['numero','estabelecimento_codigo'], ['documento','proprietario']);
    }

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', ['Número do Documento','Estabelecimento'], ['documento','proprietario']);
    }

    public function fracaoNasajon(){
        return $this->hasOne('App\FracoesNasajon', 'fracao', 'fracao');
    }

}
