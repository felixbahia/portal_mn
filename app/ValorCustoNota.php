<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\DB;

class ValorCustoNota extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'estabelecimento', 'fornecedor_cnpj', 'fornecedor_codigo', 'numero_pedido', 'data_compra', 'proforma', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function produtos(){
        return $this->hasMany('App\ValorCustoNotaProduto','valor_custo_notas_id', 'id');
    }
    public function estabelecimento_detalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento');
    }
    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'fornecedor_codigo');
    }
    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
