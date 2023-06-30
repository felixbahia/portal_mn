<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartoesMeiosEletronicosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_meioseletronicoscartoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['meioeletronicocartao'];
    
    protected $fillable = [
        'meioeletronicocartao', 
        'codigo'
    ];
}
