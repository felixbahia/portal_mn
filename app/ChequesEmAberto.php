<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChequesEmAberto extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_chequesemaberto';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['codigo', 'cod_cliente'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabelecimento',
        'cod_cliente',
        'nome_cliente',
        'banco',
        'agencia',
        'numero_conta',
        'numero_cheque',
        'valor',
        'observacao',
        'data_entrada',
        'data_vencimento',
        'situacao',
        'id_cliente'
    ];

    function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'id_cliente');
    }

}
