<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendasSantista extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'estabelecimento',
        'cliente_cnpj',
        'cliente_nome',
        'representante_codigo',
        'serie',
        'nota_id',
        'nota_fiscal',
        'emissao',
        'codigo_produto',
        'quantidade',
        'valor',
        'confirmado',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function representante(){
        return $this->hasOne('App\User', 'codigo_representante', 'representante_codigo');
    }

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_cnpj');
    }
}
