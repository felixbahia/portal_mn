<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RemessaProduto extends Model
{
    use SoftDeletes;
    protected $table = 'remessa_produtos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'estabelecimento_codigo', 'produto_codigo', 'tipo', 'pedido_compra_numero', 'pedido_compra_numero_uuid', 'operacao', 'cfop', 'preco', 'quantidade_enviada', 'quantidade_total', 'data_envio', 'faccaos_id', 'lancamento_projetos_id', 'lancamento_projeto_tecidos_id', 'lancamento_projeto_insumos_id', 'lancamento_projeto_faccoes_id', 'necessidades_compras_id', 'created_by', 'updated_by', 'deleted_by', 'pedido_id'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function projeto_detalhes(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }

    public function pedido_transferencia(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id')->withTrashed();
    }

    public function faccao(){
        return $this->hasOne('App\Faccao', 'id', 'faccaos_id');
    }

    public function pedidoRemessaNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_compra_numero_uuid');
    }
}
