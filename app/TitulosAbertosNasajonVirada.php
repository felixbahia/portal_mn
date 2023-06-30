<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosAbertosNasajonVirada extends Model
{
    public $timestamps = false;

    public $fillable = [
		'codigo', 
        'cod_cliente', 
        'nome_cliente', 
        'numero', 
        'parcela', 
        'vencimento', 
        'valor', 
        'conta_agencia', 
        'conta_agencia_digito', 
        'conta_numero', 
        'conta_digito', 
        'id_estabelecimento', 
        'titulo_emissao', 
        'nota_numero', 
        'nota_id', 
        'nota_emissao', 
        'banco_codigo', 
        'banco_nome', 
        'cnpj', 
        'id_cliente', 
        'titulo_de_terceiro', 
        'documento_terceiro', 
        'nome_terceiro', 
        'saldotitulo', 
        'nossonumero', 
        'identificadorbancario', 
        'vencimento_original', 
        'tem_prorrogacao', 
        'titulo_id', 
        'multa', 
        'desconto', 
        'observacao', 
        'enviado_para_banco', 
        'juros', 
        'datainiciomulta', 
        'vendedor_codigo', 
        'enviado_para_cartorio', 
        'enviado_para_cartorio_data', 
        'origem', 
        'origem_texto',
        'percentual_comissao'
    ];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }
    
    public function vendedor(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }

    public function comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'nota_id');
    }
    
}
