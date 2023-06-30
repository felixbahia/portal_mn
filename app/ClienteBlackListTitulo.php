<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteBlackListTitulo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'cliente_black_lists_id', 'titulo_uuid_nasajon', 'titulo_numero', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
