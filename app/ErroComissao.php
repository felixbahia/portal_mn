<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErroComissao extends Model
{

    public $timestamps = false;

    public $fillable = ['id', 'comissao_prologos', 'comissao_portal', 'pedido_prologos', 'cod_representante', 'nfe', 'data', 'numdoc', 'batch'];
}
