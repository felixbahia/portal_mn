<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;


class UsuarioClientePendente extends Model
{
    use SoftDeletes;
    
    public $fillable = [
        'cpf_cnpj',
        'novo_email',
        'telefone',
        'updated_by'
    ];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cpf_cnpj');
    }
}
