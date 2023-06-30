<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RelacaoTituloVendedorNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_titulos_vendedores';

    public $fillable = [
        'vendedor',
        'vendedor_id',
        'titulo_id',
    ];

    public function titulo(){
        return $this->hasOne('App\ChequeTituloNasajon', 'titulo_id', 'titulo_id');
    }

    public function usuario(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor');
    }
}
