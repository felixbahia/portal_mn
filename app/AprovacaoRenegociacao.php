<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AprovacaoRenegociacao extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'renegociacao_titulos_id', 'aprovacao_diretoria', 'aprovacao_automatica', 'aprovacao_cliente', 'diretoria_users_id', 'data_aprovacao_diretoria', 'data_aprovacao_automatica', 'data_aprovacao_cliente', 'created_by', 'updated_by', 'deleted_by', 'motivo_desconto_no_valor_do_titulo', 'motivo_juros_baixo_permitido', 'motivo_periodo_maior_permitido', 'motivo_parcela_com_valor_fixo', 'motivo_sem_fiador', 'motivo_fiador_casado_sem_venia_conjugal'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function renegociacaoTitulo(){
        return $this->hasOne('App\RenegociacaoTitulo', 'id', 'renegociacao_titulos_id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
