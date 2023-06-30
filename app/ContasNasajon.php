<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContasNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_contas';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['conta'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'conta',
        'codigo',
        'nome'
    ];
}
