<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Movimentacao extends Model{

    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'pgsql';
    protected $table = 'movimentacao_recalculo';
    public $timestamps = false;
    
    protected $fillable = [
        'produto_codigo', 'estabelecimento', 'data_movimentacao', 'documento', 'cliente_codigo', 'cliente_cpf_cnpj', 'tipo_operacao', 'cfop', 'quantidade', 'preco', 'aliquota', 'custo', 'custo_sem_imposto', 'saldo_movimentos', 'sinal', 'preco_composicao', 'unidade', 'frete', 'ipi', 'desconto', 'seguro', 'preco_pcmn', 'custo_pcmn', 'grupo', 'marca', 'linha', 'subgrupo', 'descricao', 'vendedor', 'preco_prepago', 'documento_original', 'estado_cliente', 'custo_prepago', 'custo_sem_imposto_prepago', 'custo_pcmn_prepago', 'custo_medio', 'valor_outras_despesas', 'valor_pis', 'valor_cofins', 'valor_aframm', 'valor_2', 'produto_procedencia', 'unidades_negocios_id', 'equipe'
        ,'custo_armazem' ,'custo_armazem_saldo'];
        
     protected $dates = ['data_movimentacao'];
    public function estabelecimento_detalhe(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento');
    }

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cliente_codigo');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'cliente_codigo');
    }

    public function detalhesVendedor(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor');
    }

    public function detalhesNotaDevolucao(){
        return $this->hasOne('App\DevolucaoNota', 'nota_fiscal', 'documento', 'estabelecimento', 'estabelecimento');
    }

    public function notasNasajon(){
        return $this->hasOne('App\NotasNasajon', ['numero', 'estabelecimento_codigo'],['documento','estabelecimento']);
    }

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', ['Número do Documento', "Estabelecimento"],['documento','estabelecimento']);
    }

    public function faturamento(){
        return $this->hasOne('App\FaturamentoOnline', ['numero_nota', 'estabelecimento', 'codigo_cadastro'],['documento', 'estabelecimento', 'cliente_codigo']);
    }

    public function faturamentoNasajon(){
        return  $this->hasOne('App\FaturamentoNotaNasajon', ['Número Documento', 'Estabelecimento', 'Cliente'],['documento', 'estabelecimento', 'cliente_codigo'])->where('TIPO','=','VENDA');
    }

    public function notasNasajonVarios(){
        return $this->hasMany('App\NotasNasajon', 'numero', 'documento');
    }

    public function produtoGrupo(){
        return  $this->hasOne('App\ProdutoGrupo', 'descricao', 'grupo');
    }
}
