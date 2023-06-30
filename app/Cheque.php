<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
	use SoftDeletes;

    protected $fillable = [
        'cliente_cpf_cnpj',
        'banco',
        'agencia',
        'conta',
        'numero_cheque',
        'valor',
        'saldo',
        'bom_para',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function cliente_detalhes(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_cpf_cnpj');
    }

    public function pedidos_prepagos(){
        return $this->hasMany('App\ChequesPedidosPrepagos', 'cheque_id', 'id');
    }
}
