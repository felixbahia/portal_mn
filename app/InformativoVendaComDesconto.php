<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InformativoVendaComDesconto extends Model
{
    protected $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;

	protected $fillable = [
        'estabelecimento',
        'pedido_portal_numero',
        'pedido_nasajon_numero',
        'pedido_data_emissao',
        'nota_numero',
        'vendedor_codigo',
        'vendedor_nome',
        'produto_codigo',
        'produto_descricao',
        'faturamento_data',
        'valor_unitario_sem_desconto',
        'valor_unitario_real_faturado',
        'quantidade_vendida'
    ];
}
