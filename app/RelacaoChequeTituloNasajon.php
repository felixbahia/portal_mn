<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RelacaoChequeTituloNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_cheques_titulos';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'cheque_id', 
        'titulo_id', 
        'documento_id', 
        'documento_numero', 
        'documento_emissao', 
    ];

    public function notas(){
        return $this->hasMany('App\NotasNasajon', 'id', 'documento_id');
    }
}
