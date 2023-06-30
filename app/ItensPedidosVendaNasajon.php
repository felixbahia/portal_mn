<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItensPedidosVendaNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedidos_itens';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['id_docfis', 'id_item'];
    protected $keyType = 'string';

    protected $fillable = ['df_linha', 'produto', 'produto_codigo', 'produto_especificacao', 'item_item', 'id_item', 'id_docfis', 'quantidadecomercial', 'quantidade_apurada', 'quantidade_faturada', 'valortotal', 'valordesconto', 'unidade', 'unidade_codigo', 'id_unidade_tributada', 'unidade_tributada', 'quantidadetributavel', 'grupo', 'subgrupo'];

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'produto', 'produto');
    }

    public function especificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
