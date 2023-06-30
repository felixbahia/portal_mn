<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StoneConfiguracaoMaquininha extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'use_without_pos_config', 
        'activate_linked_pos_config', 
        'activate_unlinked_and_linked_pos_config', 
        'activate_single_information_automatic_select', 
        'activate_dispose_transaction_any_pos', 
        'lock_app', 
        'view_error_request', 
        'display_view_cancel_pre_transaction',
        'instruction_activation_time',
        'pos_configuration_control_id',
        'cashier_number',
        'pdv_number',
        'pos_link_label',
        'pos_reference_id_to_link',
        'pos_serial_number_to_link',
        'stone_cadastro_maquininha_id',
        'vinculo',
        'created_by', 
        'updated_by', 
        'deleted_by'
    ];

    
    public function cadastroMaquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'id', 'stone_cadastro_maquininha_id');
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
