<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteBionexo extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'id', 'cpf_cnpj', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function ClienteNasajon(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj','cpf_cnpj');
    }

    public function CreatedBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function UpdateBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function DeletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
