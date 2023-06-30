<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoneCadastroMaquininha extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'razao_social', 
        'nome_fantasia', 
        'documento', 
        'stone_code', 
        'partner_stone_id', 
        'descricao', 
        'estabelecimento_stone_id', 
        'estabelecimento',
        'created_by', 
        'updated_by', 
        'deleted_by'
    ];

    public function configuracaoMaquininha(){
        return $this->hasOne('App\StoneConfiguracaoMaquininha', 'stone_cadastro_maquininha_id', 'id');
    }
    public function vinculosMaquininha(){
        return $this->hasMany('App\StoneMaquininhaVinculosFechado', 'stone_cadastro_maquininha_id', 'id');
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
