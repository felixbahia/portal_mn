<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StoneMaquininhaVinculosFechado extends Model
{
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'stone_cadastro_maquininha_id', 
        'stone_configuracao_maquininha_id',
        'serial', 
        'cashier_number', 
        'pdv_number', 
        'pos_link_label', 
        'pos_reference_id',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'created_at'
    ];
    
    public function cadastroMaquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'id', 'stone_cadastro_maquininha_id');
    }
    public function configuracaoMaquininha(){
        return $this->hasOne('App\StoneConfiguracaoMaquininha', 'id', 'stone_configuracao_maquininha_id');
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
