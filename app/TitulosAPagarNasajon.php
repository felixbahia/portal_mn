<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosAPagarNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'nsview.vw_titulos_a_pagar';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = '';

    protected $fillable = [
    	'Número do Título', 'Tipo de Título', 'Situação do Título', 'Título Provisório', 'Data de Emissão', 'Ano/Mês da Emissão', 'Ano da Emissão', 'Mês da Emissão', 'Dia da Emissão', 'Fornecedor', 'Razão Social do Fornecedor', 'Nome do Fornecedor', 'Conta', 'Nome da Conta', 'Valor', 'Valor Líquido', 'Valor da Baixa', 'Saldo do Adiantamento', 'Estabelecimento', 'Nome do Estabelecimento', 'Empresa', 'Nome da Empresa', 'Grupo Empresarial', 'Nome do Grupo Empresarial', 'Data do Vencimento', 'Ano/Mês do Vencimento', 'Ano do Vencimento', 'Mês do Vencimento', 'Dia do Vencimento', 'Data Previsão do Vencimento', 'Ano/Mês da Previsão Venc.', 'Ano da Previsão Venc.', 'Mês da Previsão Venc.', 'Dia da Previsão Venc.', 'Data da Baixa', 'Ano/Mês da Baixa', 'Ano da Baixa', 'Mês da Baixa', 'Dia da Baixa', 'percentualdesconto', 'percentualmulta', 'percentualjurosdiario', 'observacao', 'pisretido', 'cofinsretido', 'csllretido', 'irretido', 'Origem', 'INSS Retido', 'Aliquota ISS', 'ISS Retido', 'Nosso número', 'IRRF Retido na Nota', 'INSS Retido na Nota', 'Parcela', 'Total de Parcelas', 'Desconto', 'Juros', 'Multa', 'Outros Acrescimos', 'Documento Rateado', 'Número Nota', 'CNPJ/CPF do Estabelecimento', 'CNPJ/CPF do Fornecedor', 'id_pessoa', 'Forma de Pagamento', 'Desc. da Forma de Pagamento', 'Semana do Pagamento', 'Data de Competência','Id Estabelecimento'
    ];

    function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'id_pessoa');
    }

    function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'Fornecedor');
    } 

    function fornecedorDetalhes(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'Fornecedor');
    } 


    function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Fornecedor', 'Fornecedor');
    }

    function notaEntradaDetalhes(){
        return $this->hasOne('App\NotasEntradasNasajon', ['Número do Documento', 'Fornecedor'], ['Número Nota', 'Fornecedor']);
    }

    function proformaDetalhes(){
        return $this->hasOne('App\ComprasNasajon', 'proforma', 'Número Nota');
    }
}
