<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotaItensNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'ns.vw_nfes_saida_itens';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'df_linha',
        'codigo',
        'especificacao',
        'ncm',
        'valorsituacaotributariaicms',
        'cfop',
        'unidade',
        'quantidadecomercial',
        'valorunitariocomercial',
        'valortotal',
        'valordesconto',
        'valorbaseicms',
        'valoricms',
        'valoripi',
        'valoraliquotaicms',
        'valoraliquotaipi',
        'id_docfis'
    ];

    public function especificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo');
    }

    public function nota(){
        return $this->hasOne('App\NotasNasajon', 'id', 'id_docfis');
    }

    public function especificacaos(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo');
    }

    public function notaCfop(){
        return $this->hasOne('App\NotasCfopNasajon', 'id', 'id_docfis');
    }
}
