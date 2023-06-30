<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartoesBandeirasNasajon extends Model
{
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_bandeirascartoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['bandeiracartao'];

    protected $fillable = [
        'bandeiracartao', 
        'codigo'
    ];
}
