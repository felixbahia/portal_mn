<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportadorasEdi extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $connection = 'pgsql';
    protected $fillable = [
        'id', 'transportadora_cnpj', 'email', 'created_by', 'updated_by', 'deleted_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function transportadoraNome(){
        return $this->HasOne('App\TransportadorNasajon', 'cnpj', 'transportadora_cnpj');
    }

    public function transportadoraNotas(){
        return $this->HasMany('App\NotasNasajon', 'transportadora_documento', 'transportadora_cnpj');
    }

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
