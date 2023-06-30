<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoItemPortal extends Model
{
    use SoftDeletes;
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'pgsql';
    function getCodProdutoPrologosAttribute(){
        return utf8_decode($this->cod_produto);
    }

    protected $table = 'pedido_item';
    protected $fillable = [
        'pedido',
        'usuario',
        'cod_produto',
        'quantidade',
        'preco_unitario',
        'created_by',
        'updated_by',
        'valor_icms',
        'base_calculo_icms',
        'valor_ipi',
        'aliquota_icms',
        'aliquota_ipi',
        'valor_frete',
        'valor_total',
        'coluna',
        'comissao',
        'ipi_produto',
        'preco_base',
        'comissao_a',
        'comissao_b',
        'comissao_c',
        'pedido_item',
        'numero_compra',
        'preco_promocao',
        'codigo_tecidos_base',
        'codigo_desenho',
        'produto_sem_estoque',
        'tem_ipi',
        'preco_original',
        'faccao_id',
        'pedido_compras_uuid_nasajon',
        'pedido_remessa_uuid_nasajon',
        'comissao_original',
        'campanha_id',
        'tipo_comissao_campanha',
        'incentivo_campanha'
    ];

    public function info_produto(){
    	return $this->hasOne('App\Produto', 'CODPRD', 'cod_produto');
    }

    public function info_produtoNasjon(){
    	return $this->hasOne('App\ProdutoNasajon', 'codigo', 'cod_produto');
    }

    public function infoProdutoUsoConsumo(){
    	return $this->hasOne('App\ProdutoUsoConsumoNasajon', 'codigo', 'cod_produto');
    }

    public function pedido_portal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido');
    }

    public function estoque(){
        return $this->hasOne('App\ProdutosEstoque', 'codigo_produto', 'cod_produto');
    }

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'cod_produto');
    }
    
    public function precos(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'cod_produto');
    }

    public function tecidosBase(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_tecidos_base');
    }

    public function desenho(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_desenho');
    }

    public function comprasNasajon(){
        return $this->hasOne('App\ComprasNasajon', ['numero_pedido', 'cod_produto'], ['numero_compra', 'cod_produto'])->orderBy('situacao_item', 'desc');
    }

    public function pedidoMaiorEstoqueDetalhes(){
        return $this->hasOne('App\PedidoMaiorEstoque', 'produto_codigo', 'cod_produto');
    }

    public function detalhesFaccao(){
        return $this->hasOne('App\Faccao', 'id', 'faccaos_id');
    }

    public function detalhesRemessa(){
        return $this->hasOne('App\RemessaProduto', 'pedido_compra_numero_uuid', 'pedido_remessa_uuid_nasajon');
    }

    public function detalhesCompras(){
        return $this->hasOne('App\ComprasNasajon', 'id_nota', 'pedido_compras_uuid_nasajon');
    }

    public function carrinhoCompraItens(){
        return $this->hasOne('App\CarrinhoCompraItem', 'pedido_item_id', 'id');
    }

    public function detalhesCustos(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'cod_produto');
    }

    public function estoqueTotal(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'cod_produto');
    }

    public function campanha(){
        return $this->hasOne('App\Campanha', 'id', 'campanha_id')->withTrashed();
    }

    public function tipoComissao(){
        return $this->hasOne('App\CampanhaApuracaoTipo', 'id', 'tipo_comissao_campanha');
    }

    public function usuario(){
        return $this->hasOne('App\User', 'id', 'usuario');
    }

} 
