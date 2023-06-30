<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NivelAprovacao extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'descricao', 'tipo_usuario_id'
    ];
}
