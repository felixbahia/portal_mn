<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoPreco extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $primaryKey = 'codigo_produto';

    protected $fillable = [
        'codigo_produto',
        'grupo',
        'marca',
        'linha',
        'subgrupo',
        'descricao',
        'preco_real',
        'preco_dolar',
        'created_by',
        'updated_by',
        'deleted_by',
        'status'
    ];

    public function criadoPor(){
        return $this->belongsTo('App\User', 'id', 'created_by');
    }

    public function modificadoPor(){
        return $this->belongsTo('App\User', 'id', 'updated_by');
    }

    public function excluidoPor(){
        return $this->belongsTo('App\User', 'id', 'deleted_by');
    }

    public function produtoPrologos(){
        return $this->hasOne('App\Produto', 'CODPRD', 'codigo_produto');
    }
}
