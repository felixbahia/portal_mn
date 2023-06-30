<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TituloInformacao extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'estabelecimento_codigo', 'mes_emissao', 'ano_emissao', 'periodo_vencimento', 'quantidade', 'valor', 'valor_pre_pago', 'representante_codigo', 'equipe', 'unidades_negocios_id'
    ];
}
