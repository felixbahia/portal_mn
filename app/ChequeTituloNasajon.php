<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChequeTituloNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_cheques_titulos';

    public $fillable = [
        'cheque_id',
        'titulo_id',
        'titulo_numero',
        'documento_id',
        'documento_numero',
        'documento_emissao',
        'valor_titulo'
    ];

    public function nota(){
        return $this->hasOne('App\NotasNasajon', 'id', 'documento_id');
    }

    public function titulosAbertos(){
        return $this->hasOne('App\TitulosEmAbertoNasajonPortal', 'titulo_id', 'titulo_id');
    }

    public function titulosPagos(){
        return $this->hasOne('App\TitulosPagosNasajon', 'numero', 'titulo_numero');
    }

    public function cheque(){
        return $this->hasOne('App\ChequeNasajon', 'cheque_id', 'cheque_id');
    }

    public function vendedor(){
        return $this->hasOne('App\RelacaoTituloVendedorNasajon', 'titulo_id', 'titulo_id');
    }

}
