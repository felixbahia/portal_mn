<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusClienteNovo extends Model
{
    use SoftDeletes;
    protected $fillable = ['status'];

    public function clientes(){
        return $this->belongsTo('App\ClienteNovo', 'id', 'statu');
    }
}
