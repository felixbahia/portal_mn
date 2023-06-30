<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosVendedor998Nasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_titulos_portal_vendedor_998';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'titulo_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'codigo',
        'cliente_id',
        'cod_cliente',
        'nome_cliente',
        'numero',
        'parcela',
        'emissao',
        'vencimento',
        'valor_titulo',
        'sinal',
        'documento_numero',
        'documento_id',
        'vencimento_original',
        'tem_prorrogacao',
        'vendedor_codigo',
        'valor',
        'banco_nome',
        'banco_codigo',
        'situacao_inteiro',
        'situacao',
        'titulo_id',
        'multa',
        'desconto',
        'observacao',
        'enviado_para_banco',
        'juros',
        'datainiciomulta',
        'saldotitulo',
        'id_cliente',
        'nossonumero'
    ];

    public function notas(){
        return $this->hasMany('App\NotasNasajon', 'id_transportadora', 'id');
    }

    public function cenprotTitulos(){
        return $this->hasOne('App\CenprotTitulo', 'titulo_id', 'titulo_id')->withTrashed();
    }
}
