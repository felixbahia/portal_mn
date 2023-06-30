<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaLogItens extends Model
{
    
    use SoftDeletes;
    
    public $fillable = [
        'devolucao_nota_log_id',
        'codigo_produto',
        'quantidade'
    ];

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }
}
