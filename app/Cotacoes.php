<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cotacoes extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'ns.vw_cotacoes';

    public $protected = [
        'data',
        'valor',
        'cotacao',
        'moeda',
        'lastupdate',
        'tenant',
        'datastr'
    ];

    public function moeda(){
        return $this->hasOne('App\Moeda', 'moeda', 'moeda');
    }
}
