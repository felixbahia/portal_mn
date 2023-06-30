<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaturamentoNotaNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_faturamentos';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'Identificador Documento','Identificador Documento Rateado','Identificador Estabelecimento','Estabelecimento','Nome do Estabelecimento','Origem do Documento','Número Documento','Data de Emissão','Identificador Cliente','Cliente','Nome do Cliente','Documento do Cliente','Valor Documento','Tipo do Documento','Identificador Operação','Código da Operação','Descrição da Operação','Transportadora - Id','Transportadora - CNPJ','Transportadora - Código','Forma Pagamento - Código','Forma Pagamento - Descrição','Frete - Modalidade','Vendedor - Código','Vendedor - Nome','Vendedor - Percentual Comissão','Quantidade Volumes','Peso Líquido','Entrega - UF','Pedido - Número', 'Id_Nota', 'Id_Nota_Origem', 'TIPO','cfop'
    ];

    public function itens_faturamento(){
        return $this->hasMany('App\FaturamentoItemNasajon', 'Identificador Documento', 'Identificador Documento');
    }

    public function vendedor_detalhes(){
        return $this->hasOne('App\VendedorNasajon', 'codigo', 'Vendedor - Código');
    }

    public function pedido(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'Id_Nota');
    }

    public function pedidoNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'nf_id', 'Id_Nota');
    }

    public function pedidoNasajonDevolucao(){
        return $this->hasOne('App\PedidosVendaNasajon', "notafiscal_id", "Id_Nota_Origem")->where("Código da Operação",'ilike','%DEVOLUCAO%');
    }

    public function pedido_itens(){
        return $this->hasMany('App\NotaVendaItemNasajon', 'id_nota', 'Id_Nota');
    }

    public function revisao_comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'Id_Nota');
    }
    
    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', "Identificador Cliente");
    }

    public function devolucoes(){
        return $this->hasMany('App\FaturamentoNotaNasajon','Id_Nota_Origem', 'Id_Nota');
    }

    public function nota_origem(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Id_Nota', 'Id_Nota_Origem');
    }

    public function estabelecimentoDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'estabelecimento', 'Identificador Estabelecimento');
    }

    public function detalhesDeCondicoesPagamentos(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'Identificador Documento');
    }

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'estabelecimento', 'Identificador Estabelecimento');
    }
}
