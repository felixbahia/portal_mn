<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidosReservaProdutoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedido_nota_aberto';
    protected $keyType = 'string';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'codigo_estabelecimento',
        'codigo_produto',
        'id',
        'numero',
        'documento_operacao_codigo',
        'emissao',
        'quantidade',
        'documento_operacao_descricao',
        'eh_pedido',
        'quantidade_faturada'
    ];

    public function notasNasajon(){
        return $this->hasOne('App\NotasNasajon', 'id', 'id');
    }

    public function notasEmAbertoNasajon(){
        return $this->hasOne('App\NotasEmAbertoNasajon', 'id', 'id');
    }

    public function pedidosVendaNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'id');
    }

}
