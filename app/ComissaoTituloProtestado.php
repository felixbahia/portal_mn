<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComissaoTituloProtestado extends Model
{
    use SoftDeletes;
    //
    public $fillable = [
        'nota_id',
        'vencimento',
        'saldo',
        'titulo_emissao',
        'parcela',
        'codigo_representante'
    ];

    public function vendedorDetalhes(){
        return $this->hasOne('App\User', 'codigo_representante', 'codigo_representante');
    }

    public function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_id');
    }

    public function debito_comissao(){
        return $this->hasOne('App\LancamentoDebCredVendedor', 'nota_uuid', 'nota_id')
            ->where('tipo', 'D')
            ->where('parcela', $this->parcela);
    }

}
