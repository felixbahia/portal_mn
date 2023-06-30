<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParametrosAprovacao extends Model
{
    use SoftDeletes;

	protected $table = "parametros_aprovacao";
	protected $fillable = ['estabelecimento', 'tipo_usuario_id', 'percentual_desconto', 'prazo_adicional', 'created_by', 'modified_by'];

    public function tipoUsuario()
    {
        return $this->hasOne('App\TipoUsuario', 'id', 'tipo_usuario_id');
    }

}
