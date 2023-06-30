<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartoesOperadorasNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_operadorascartoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['operadoracartao'];

    protected $fillable = [
        'operadoracartao', 
        'codigo'
    ];
}
