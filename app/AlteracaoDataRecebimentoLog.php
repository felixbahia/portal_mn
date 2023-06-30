<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AlteracaoDataRecebimentoLog extends Model
{
    public $fillable = [
        'estabelecimento',
        'numero_pedido',
        'data_anterior',
        'data_atual',
        'usuario',
    ];

    public function itensPedido(){
        return $this->hasMany('App\ComprasNasajon', 'numero_pedido', 'numero_pedido')->where('estabelecimento', $this->estabelecimento);
    }
}
