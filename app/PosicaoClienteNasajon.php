<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PosicaoClienteNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_posicao_clientes';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cod_cliente', 'nome_cliente', 'cnpj_cliente', 'cliente_desde', 'limite_de_credito', 'titulos_a_vencer', 'titulos_vencidos', 'creditos_a_vencer', 'creditos_vencidos', 'pagos_em_atraso', 'ultimo_titulo_atraso', 'data_ultimo_titulo_atraso', 'dias_ultimo_titulo_atraso', 'maior_titulo_atraso', 'data_maior_titulo_atraso', 'dias_maior_titulo_atraso', 'numero_ultima_venda', 'emissao_ultima_venda', 'valor_ultima_venda', 'numero_maior_venda', 'emissao_maior_venda', 'valor_maior_venda', 'total_pago_12_meses', 'quantidade_pedidos', 'valor_pedidos'
    ];
}
