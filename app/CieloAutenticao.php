<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloAutenticao extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabelecimento',
        'client_id',
        'merchant_id',
        'merchant_key',
        'access_token',
        'lio',
        'ecommerce',
        'sandbox',
        'token_api'
    ];

}
