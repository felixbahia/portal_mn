<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItensNotasCompraNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_documentos_compras_produtos';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['numero', 'produto_codigo'];

    protected $guarded = [
        'numero',
        'emissao',
        'estabelecimento_codigo',
        'estabelecimento_cnpj',
        'fornecedor_codigo',
        'fornecedor_cnpj',
        'fornecedor_nome',
        'produto_codigo',
        'unidade',
        'quantidade',
        'valorunitario',
        'valortotal',
        'custo',
        'documento_operacao_codigo',
    ];

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'fornecedor_codigo');
    }
}
