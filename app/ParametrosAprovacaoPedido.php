<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParametrosAprovacaoPedido extends Model
{
    use SoftDeletes;
    
	protected $table = "parametros_aprovacao_pedido";
	protected $fillable = ['estabelecimento', 'maximo_tempo_inativo', 'maximo_atraso_medio', 'maximo_ultimo_atraso', 'maximo_maior_atraso', 'maximo_duplicatas_vencidas', 'maximo_de_limite_credito'];

}
