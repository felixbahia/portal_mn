<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoricoFinanceiroCliente extends Model
{
    protected $fillable = [
        'cliente_raiz_cnpj',
        'contato',
        'retorno_possivel_id',
        'titulos_em_aberto_quantidade',
        'titulos_em_aberto_valor',
        'titulos_em_aberto_maior_atraso',
        'observacao',
        'email_enviado',
        'created_by',
        'updated_by'
    ];

    public function titulos(){
        return $this->hasMany('App\HistoricoFinanceiroClienteTitulo', 'historico_financeiro_clientes_id', 'id');
    }

    public function retornoCobranca(){
        return $this->hasOne('App\RetornoCobranca', 'id', 'retorno_possivel_id')->withTrashed();
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by')->withTrashed();
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
}
