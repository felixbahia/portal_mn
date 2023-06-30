<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComprasFuturas extends Model
{
    protected $connection = 'srv_ww';
    protected $table = 'WWFUT';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['CODPRD', 'ESTABEL', 'ANO', 'MES', 'QUINZENA', 'NUMPEDCMP'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODPRD', 'ESTABEL', 'ANO', 'MES', 'QUINZENA', 'QTDCOMPRA', 'NUMPEDCMP'
    ];

}
