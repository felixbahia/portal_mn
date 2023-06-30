<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConceitosCliente extends Model
{
    use SoftDeletes;
    protected $fillable = ['codigo', 'descricao', 'created_by', 'updated_by', 'deleted_by'];

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
