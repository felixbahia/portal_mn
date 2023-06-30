<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasEntradasNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_nfe_entrada';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['identificador_documento'];
    protected $keyType = 'string';

    protected $fillable = [
        "Identificador Documento",
		"Identificador Estabelecimento",
		"Estabelecimento",
		"Nome do Estabelecimento",
		"Tipo do Documento",
		"Número do Documento",
		"Data de Emissão",
		"Identificador do Fornecedor",
		"Fornecedor",
		"Nome do Fornecedor",
		"CNPJ/CPF do Fornecedor",
		"Valor do Documento",
		"Identificador da Operação",
		"Código da Operação",
		"Descrição da Operação",
		"Identificador da Transportadora",
		"CNPJ/CPF da Transportadora",
		"Transportadora",
		"Nome da Transportadora",
		"Modalidade do Frete",
		"Quantidade Volumes",
		"Peso Líquido",
		"Desconto",
		"Valor Frete",
		"Valor Seguro",
		"Valor Outras Despesas",
		"Valor IPI",
		"Valor ICMS-ST",
		"Pedido",
		"Data de Entrada",
		"Chave NE",
		"Informações Adicionais"
    ];

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'id', 'Identificador do Fornecedor');
    }

    public function itens_nota(){
        return $this->hasMany('App\NotasEntradasItensNasajon', 'Identificador Documento', "Identificador Documento");
    }

    public function primeiro_itens_nota(){
        return $this->hasOne('App\IntesNotasNasajon', 'id_nota', "Identificador Documento");
    }

    public function itens(){
        return $this->hasMany('App\IntesNotasNasajon', 'id_nota', "Identificador Documento");
    }

    public function nota_entrada(){
        return $this->hasMany('App\PedidoComprasAssociacaoNotaNasajon', 'id_nota', "Identificador Documento");
    }

    public function condicaoDePagamento(){
        return $this->hasOne('App\CondicoesPagamentoNasajon', 'id_docfis', 'Identificador Documento');
    }

    public function scoreFornecedores(){
        return $this->hasOne('App\ScoreFornecedore', 'nota_entrada_id', 'Identificador Documento');
    }

    public function primeiroItemNota(){
        return $this->hasOne('App\NotasEntradasItensNasajon', 'Identificador Documento', "Identificador Documento");
    }

	public function documentosAssociacoes(){
        return $this->hasMany('App\DocumentosAssociacoesNasajon', 'id_origem', "Identificador Documento");
    }
}
