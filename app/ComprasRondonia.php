<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComprasRondonia extends Model
{
    protected $connection = 'srv_rondonia';
    protected $table = 'TBPCM2';
    public $timestamps = false;
    public $incrementing = false;
    protected $dateFormat = 'Y-m-d h:i:s';
    
    protected $fillable = [
        'NUMPED',
        'CODCAD',
        'CDSITATUAL',
        'CODVCT',
        'PENDENCIA',
        'VALTOTPED',
        'FLAG_IAD',
        'ESTABEL',
        'OBS',
        'CODUSU_COMPRADOR',
        'DATAPED',
        'DTENTREGA',
        'DTORIGINAL',
        'DTSITATUAL',
    ];

    protected $dates = [
        'DATAPED',
        'DTENTREGA',
        'DTORIGINAL',
        'DTSITATUAL',
    ];

    
}
