<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentosAssociacoesNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_documentosassociacoes';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        "id_nota","id_origem"
    ];

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Identificador Documento', 'id_origem');
    }

    public function notaSaida(){
        return $this->hasOne('App\NotasNasajon', 'id', 'id_nota');
    }
}
