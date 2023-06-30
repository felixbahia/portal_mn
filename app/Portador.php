<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Portador extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPOR1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODPORT';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'CODPORT', 'PORTADOR', 'IDENTIFICACAO', 'OBS', 'DX', 'FLAG_IAD', 'BANCO_VINCULADO'
    ];
}
