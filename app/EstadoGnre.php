<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoGnre extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'estado','liminar','created_by', 'updated_by', 'deleted_by'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function estado(){
        return $this->hasOne("App\CepEstado", "uf", "estado");
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletadoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}