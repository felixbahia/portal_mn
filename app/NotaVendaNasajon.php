<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotaVendaNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_notas_de_venda';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'cod_estabelecimento','nome_estabelecimento','documento_cliente','nome_cliente','id_nota','numero','serie','emissao','data_saida','naturezaoperacao','valor','frete','seguro','desconto','outras','documento_transportadora','nome_transportadora'
    ];

    public function item(){
        return $this->hasMany('App\NotaVendaItemNasajon', 'id_nota', 'id_nota');
    }

    public function pedido(){
        return $this->hasOne('App\PedidosVendaNasajon', 'notafiscal_id', 'id_nota');
    }

    public function faturamento(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Identificador Documento', 'id_nota');
    }

    public function revisao_vendedor_comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'id_nota');
    }

    public function faturamento_nota_devolucao(){
        return $this->hasMany('App\FaturamentoNotaNasajon', 'Id_Nota_Origem', 'id_nota');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'documento_cliente');
    }

    public function transportadora(){
        return $this->hasOne('App\TransportadorNasajon', 'cnpj', 'documento_transportadora');
    }

    public function detalhesTitulosPagosNasajon(){
        return $this->hasMany('App\TitulosPagosNasajon', 'documento_id', 'id_nota');
    }

    public function estabelecimentoDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'cod_estabelecimento');
    }

    public function tituloBaixado(){
        return $this->hasMany('App\TituloPagamentoNasajon', 'documento_id', 'id_nota');
    }
}
