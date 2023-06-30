<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BancoContabil extends Model
{
    protected $table = 'banco_contactb';
    public $timestamps = false;
    protected $primaryKey = ['estabel', 'codbco'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabel', 'codbco', 'contactb'
    ];

}
