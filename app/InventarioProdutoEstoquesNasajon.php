<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioProdutoEstoquesNasajon extends Model
{
    protected $table = 'inventario_produtos_estoques_nasajon';
    protected $primaryKey = 'codigo_produto';
    protected $fillable = [
        'estabelecimento', 'codigo_produto', 'saldo', 'data_atulalizacao'
    ];
    
    protected $dateFormat = 'U';

    protected $dates = ['data_atulalizacao'];

    public function produto(){
        return $this->belongsTo('App\ProdutoNasajon', 'codigo_produto', 'codigo');
    }
}
