<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasComissaoCalculo extends Model
{
	use SoftDeletes;
    
    protected $fillable = [
		'id',
		'pedido_id',
		'nota_id',
		'pedido_nasajon_id',
		'created_by',
		'updated_by',
		'deleted_by',
		'porcetagem_comissao_gerente',
		'id_nota_devolucao',
		'valor_nota',
		'valor_nota_devolucao',
		'quantidade_nota',
		'quantidade_nota_devolucao',
	];

    public function notaDevolucao(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Id_Nota_Origem', 'id_nota_devolucao');
    }
    public function comissaoItens(){
        return $this->hasMany('App\CampanhasComissaoCalculoIten', 'campanha_comissao_calculo_id', 'id');
    }
    public function comissaoItensLixo(){
        return $this->hasMany('App\CampanhasComissaoCalculoIten', 'campanha_comissao_calculo_id', 'id');
    }
    public function notas(){
        return $this->hasOne('App\NotaVendaNasajon', 'id', 'nota_id');
    }
    public function pedidoNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_nasajon_id');
    }
    public function pedidoPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }
    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
