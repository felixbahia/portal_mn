<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComissaoAjusteZerada extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'titulo_id', 'vendedor_id', 'comissao_anterior', 'comissao_atual'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
}
