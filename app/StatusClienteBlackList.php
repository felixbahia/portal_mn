<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatusClienteBlackList extends Model
{
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'descricao'
    ];
}
