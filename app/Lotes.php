<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lotes extends Model
{
    protected $table = 'lotes';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'nome_gerado', 'status', 'qtd_gerados', 'qtd_erros', 'created_at'
    ];
    
}
