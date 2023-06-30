<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChequesEmAbertoNasajon extends Model
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
        'estabelecimento', 'cod_cliente', 'nome_cliente', 'banco', 'agencia', 'cheque_id', 'numero_conta', 'numero_cheque', 'valor', 'observacao', 'data_entrada', 'data_vencimento', 'situacao'
    ];

    public function cliente(){
        return $this->hasMany('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }

    public function cheques(){
        return $this->hasOne('App\RelacaoChequeTituloNasajon', 'cheque_id', 'cheque_id');
    }

    public function notas(){
        return $this->hasMany('App\ChequeTituloNasajon', 'cheque_id', 'cheque_id');   
    } 
}
