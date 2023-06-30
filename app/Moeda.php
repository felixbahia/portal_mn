<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Moeda extends Model
{
    protected $database = 'nasajon';
    protected $table = 'ns.moedas';

    public $protected = [
        'codigo',
        'nomesingular',
        'nomeplural',
        'fracaosingular',
        'fracaoplural',
        'simbolo',
        'decimais',
        'moeda',
        'lastupdate',
        'tenant'
    ];
}
