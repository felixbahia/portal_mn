<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TituloZeradoMultaJuro extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'titulo_numero', 'titulo_uuid', 'titulo_vencimento', 'percentualjurosdiario', 'juros', 'multa'
    ];

    protected $dates = ['titulo_vencimento'];
}
