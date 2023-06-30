<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assinatura extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'codigo_html',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletedby(){    
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
