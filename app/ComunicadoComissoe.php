<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ComunicadoComissoe extends Model
{
    use SoftDeletes;
    protected $fillable = ['link', 'data_confirmacao', 'confirmacao','tipo','vendedor_codigo','valor_meta','valor_comissao','created_by', 'updated_by', 'deleted_by','created_at','updated_at','deleted_at'];

    public function vendedor(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
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
