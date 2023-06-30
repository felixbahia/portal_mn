<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartoesContratosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_contratoscartoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['contratocartao'];

    protected $fillable = [
        'contratocartao', 
        'estabelecimento', 
        'operadoracartao', 
        'bandeiracartao', 
        'meioeletronicocartao', 
        'codigo',
        'tipooperacao'
    ];
}
