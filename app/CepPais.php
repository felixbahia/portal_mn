<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CepPais extends Model
{
    protected $connection = 'cep';
    protected $table = 'pais';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'bacen';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','nome','nome_pt','iso2','iso3','bacen'
    ];

}
