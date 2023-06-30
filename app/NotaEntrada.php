<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaEntrada extends Model
{
    use SoftDeletes;
    protected $table = 'notas_entradas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','estabelecimento','data_entrada','codigo_produto','nota','nota_serie','pedido','proforma','forncedor_codigo','forncedor_cpf_cnpj','unidade','quantidade','preco_real','preco_dolar','origem'
    ];

    public function notaEntradaNasajon(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Estabelecimento', 'estabelecimento')->where('Número do Documento', explode(' ', $this->nota)[0]);
    }    
}
