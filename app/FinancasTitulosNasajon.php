<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinancasTitulosNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'financas.vwtitulos';
    public $timestamps = false;
    
	protected $fillable = [
        'id','observacao','numero'

    ];
}
