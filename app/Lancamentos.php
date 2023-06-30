<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lancamentos extends Model
{
    protected $table = 'lancamentos';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'contactb_debito', 'contactb_credito', 'status', 'datahora_baixa', 'valor_baixa', 'historico', 'ncheque', 'ndoc', 'codcad', 'estabel', 'codbco', 'nparc', 'juros', 'desconto'
    ];

}
