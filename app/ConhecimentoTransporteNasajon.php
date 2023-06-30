<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConhecimentoTransporteNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_conhecimentotransporte';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
    	'estabelecimento_codigo','numero', 'emissao', 'modelo', 'serie', 'cfop', 'valorfrete','idtransportador', 'codigotransportador','nometransportador','cnpjtransportador','codigocliente', 'nomecliente'
    ];

    public function transportadoras(){
        return $this->hasOne('App\TransportadorNasajon', 'id', 'idtransportador');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'codigocliente');
    }

    public function emitidas(){
        return $this->hasOne('App\Notas_Transportadoras_Lancada', 'cte_numero', 'numero');
    }
}
