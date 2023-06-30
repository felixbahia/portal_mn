<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TituloAPagarInformacaoMes extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'titulo_a_pagar_informacao_mes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'estabelecimento_codigo', 'mes_emissao', 'ano_emissao', 'diferenca_mes', 'quantidade', 'valor', 'valor_pre_pago', 'representante_codigo', 'equipe', 'unidades_negocios_id'
    ];
}
