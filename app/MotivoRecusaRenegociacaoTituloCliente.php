<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoRecusaRenegociacaoTituloCliente extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'renegociacao_titulos_id', 'renegociacao_titulo_avalistas_id', 'descricao', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function avalista(){
        return $this->hasOne('App\RenegociacaoTituloAvalista', 'id', 'renegociacao_titulo_avalistas_id');
    }
}
