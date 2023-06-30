<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BuscaDesenhoTemp extends Model
{
    protected $connection = 'pgsql';
    
    public $fillable = ['filtro'];

}
