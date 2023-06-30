<?php

namespace App;


use Illuminate\Database\Eloquent\Model;


class RomaneioNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_ra_importacaonota_romaneio';
    public $timestamps = false;
    public $incrementing = false;

    public $guarded = [
        'documento_numero', 'documento_serie', 'ra_codigo', 'ra_data', 'ra_status', 'tipo_ra', 'item_id', 'item_codigo', 'peca_codigo', 'peca_id', 'peca_quantidade', 'conferido' ,'fornecedor','estabelecimento_nome','estabelecimento_codigo','estabelecimento_id','status','chavene'
        ,'entrada_origem_id','entrada_origem_fornecedor_codigo','entrada_origem_fornecedor_nome'
    ];


    public function coletor(){
    	return $this->hasOne('App\LogColetor', 'peca_codigo', 'peca_codigo');
    }

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'item_codigo');
    }
    public function nota(){
          return $this->hasOne('App\NotasEntradasNasajon', "Chave NE", 'chavene');
  
    }
    public function notaOrigem(){
        return $this->hasOne('App\NotasEntradasNasajon', "Identificador Documento", 'entrada_origem_id');

  }

  public function coletorRomaneio(){
    return $this->hasOne('App\LogColetorRomaneio', 'numero_nota', 'documento_numero');
}

}
