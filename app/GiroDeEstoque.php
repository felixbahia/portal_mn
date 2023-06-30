<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GiroDeEstoque extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'codigo_produto',
        'descricao',
        'grupo',
        'linha',
        'marca',
        'origem',
        'estoque',
        'ultima_compra',
        'consumo_01_mes',
        'consumo_02_mes',
        'consumo_03_mes',
        'consumo_04_mes',
        'consumo_05_mes',
        'consumo_06_mes',
        'consumo_07_mes',
        'consumo_08_mes',
        'consumo_09_mes',
        'consumo_10_mes',
        'consumo_11_mes',
        'consumo_12_mes',
        'percentual_estoque',
        'pedidos_venda',
        'vendas_aberto',
        'unidade',
        'compras_mes_atual',
        'compras_proximo_mes',
        'compras_mes_seguinte',
        'compras_proximos_meses',
        'preco_venda',
        'custo_gerencial',
        'compras_aberto_vendas'
    ];

    public function reserva(){
        return $this->hasMany('App\PedidosReservaProdutoNasajon', 'codigo_produto', 'codigo_produto');
    }

    public function produtoEstoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
    }
}
