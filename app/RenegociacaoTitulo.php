<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\DB;


class RenegociacaoTitulo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_cpf_cnpj',
        'juros_mes',
        'parcela_quantidade',
        'data_inicial',
        'data_final',
        'created_by',
        'updated_by',
        'deleted_by',
        'status_renegociacao_titulos_id',
        'juro_atualizacao_titulo',
        'tarifa_bancaria_atualizacao_titulo',
        'tarifa_bancaria_renegociacao',
        'data_inicial_renegociacao',
        'encargos',
        'clicksign_documentos_id',
        'confissao_divida'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj','cliente_cpf_cnpj');
    }

    public function valorTotalComJuros(){
        return $this->hasOne('App\RenegociacaoTituloParcela', 'renegociacao_titulos_id', 'id')
            ->select(DB::raw("sum(valor) as total"));
    }

    public function valorTotalTitulo(){
        return $this->hasOne('App\RenegociacaoTituloTitulo', 'renegociacao_titulos_id', 'id')
            ->select(DB::raw("sum(titulo_valor) as total"));
    }

    public function titulos(){
        return $this->hasMany('App\RenegociacaoTituloTitulo', 'renegociacao_titulos_id', 'id');
    }

    public function parcelas(){
        return $this->hasMany('App\RenegociacaoTituloParcela', 'renegociacao_titulos_id', 'id');
    }

    public function motivoRecusa(){
        return $this->hasOne('App\MotivoRecusaRenegociacaoTitulo', 'renegociacao_titulos_id', 'id');
    }

    public function motivoRecusaCliente(){
        return $this->hasOne('App\MotivoRecusaRenegociacaoTituloCliente', 'renegociacao_titulos_id', 'id')->orderBy('id', 'desc');
    }

    public function avalistas(){
        return $this->hasMany('App\RenegociacaoTituloAvalista', 'renegociacao_titulos_id', 'id');
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

    public function statusDetalhes(){
        return $this->hasOne('App\StatusRenegociacaoTitulo', 'id', 'status_renegociacao_titulos_id');
    }

    public function aprovacaoRenegociacao(){
        return $this->hasOne('App\AprovacaoRenegociacao', 'renegociacao_titulos_id','id');
    }

    public function parcelasPorCnpjEstabelecimento(){
        return $this->hasMany('App\RenegociacaoTituloParcelaCnpjEstabel', 'renegociacao_titulos_id', 'id')->orderBy('estabelecimento_codigo');
    }

    public function clicksignDocumento(){
        return $this->hasOne('App\ClicksignDocumento', 'id','clicksign_documentos_id');
    }
}
