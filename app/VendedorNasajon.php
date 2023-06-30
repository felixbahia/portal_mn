<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendedorNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_vendedores';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'codigo';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'id', 'codigo', 'nome'
    ];
    
}
