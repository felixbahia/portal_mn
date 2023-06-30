<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaProduto extends Model
{
    use SoftDeletes;
    
    public $fillable = [
        'devolucao_nota_id',
        'produto_id',
        'quantidade',
        'quantidade_recebida',
        'created_by',
        'deleted_by'
    ];

    public function requisicao_devolucao(){
        return $this->belongsTo('App\DevolucaoNota', 'id', 'devolucao_nota_id');
    }

    public function produto_na_nota(){
        return $this->hasOne('App\NotaVendaItemNasajon', 'id_item_nota', 'produto_id');
    }

}
