<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoUsoConsumoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_produtos_uso_consumo';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'produto';
    protected $keyType = 'string';
    

    public $guarded = [
        'produto',
        'codigo',
        'especificacao',
        'precovenda',
        'unidade',
        'marca',
        'grupo',
        'linha',
        'precovenda_dolar',
        'subgrupo',
        'ncm',
        'composicao',
        'largura',
        'gramatura',
        'codigodebarras',
        'pesobruto',
        'pesoliquido',
        'ipi',
        'incentivo_sp',
        'origemmercadoria',
        'data_criacao',
        'controlalote',
        'figuratributaria',
        'grupodeinventario',
        'controlafracao'
    ];

    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'codigo');
    }

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo');
    }

    public function unidadeMedida(){
        return $this->hasOne('App\UnidadeMedidaNasajon', 'codigo', 'unidade');
    }
}
