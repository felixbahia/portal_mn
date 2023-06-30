<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondicoesPagamentoWeb extends Model
{
    use SoftDeletes;
    

    use \Awobaz\Compoships\Compoships;

    protected $connection = 'pgsql';
    protected $table = "condicoes_pagamento_web";

    protected $fillable = [
        "descricao", "media", "id_web", "liberado_representante", "created_by", "modified_by", "nasajon", "nasajon_forma_pagamento", "nasajon_parcela", "ativo"
    ];
    
    public function clientes(){
    	return $this->hasMany('App\ClientesVencimentos', 'condicao_id', 'id');
    }

    public function vencimentos_detalhe(){
        return $this->hasOne('App\Vencimentos', 'CODVCT', 'id_web');
    }

    public function parcelas(){
        return $this->hasOne('App\ParcelamentoNasajon', 'parcelamento', 'nasajon_parcela');
    }

    public function forma_pagamento(){
        return $this->hasOne('App\FormaPagamentoNasajon', 'formapagamento', 'nasajon_forma_pagamento');
    }
}
