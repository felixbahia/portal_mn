<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasEntradasItensNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_compras_itens';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Identificador Documento',
		'Identificador Estabelecimento',
		'Estabelecimento',
		'Nome do Estabelecimento',
		'Número do Documento',
		'Data de Emissão',
		'Identificador do Fornecedor',
		'Fornecedor',
		'Nome do Fornecedor',
		'CNPJ/CPF do Fornecedor',
		'Valor do Documento',
		'Tipo do Documento',
		'Identificador da Operação',
		'Código da Operação',
		'Descrição da Operação',
		'Mês',
		'Ano',
		'Item - Código',
		'Item - Descricao',
		'Item - Quantidade',
		'Item - Valor Unitário',
		'Item - Unidade',
		'Item - Valor Total',
		'Item - CFOP',
		'Desconto',
		'Valor Frete',
		'Valor Seguro',
		'Valor Outras Despesas',
		'Valor IPI',
		'Valor ICMS-ST',
		'Data de Entrada',
		'Valor ICMS',
		'Valor Desp. Aduaneiras',
		'Valor Out. Desp',
		'Valor  Desp. Acessorias',
		'Valor  AFRAMM',
		'Valor SISCOMEX',
		'Valor PIS',
		'Valor COFINS',
		'Valor II'
    ];

    public function produtoDetalhesNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', "Item - Código");
    }
}
