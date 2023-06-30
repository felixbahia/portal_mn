<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoClienteBlackList extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'id','motivo', 'created_by', 'updated_by', 'deleted_by',
    ];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
