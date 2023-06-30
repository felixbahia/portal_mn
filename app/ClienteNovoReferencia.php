<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteNovoReferencia extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cliente_novo_id', 'empresa', 'contato', 'telefone_ddd', 'telefone', 'estado', 'cidade', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function cliente_novo(){
        return $this->hasOne('App\ClienteNovo', 'cliente_novo_id', 'id');
    }
}
