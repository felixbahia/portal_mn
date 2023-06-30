<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Adldap\Laravel\Traits\HasLdapUser;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles, SoftDeletes, HasLdapUser;

    protected $connection = 'pgsql';    
    protected $guard_name = 'web';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'celular', 'tipo_usuario_id', 'empresa_padrao_id', 'cliente_padrao_id', 'regiao_atuacao', 'setor', 'password', 'responsavel', 'codigo_representante', 'photo', 'comissao_a', 'comissao_b', 'comissao_c', 'codigo_nasajon', 'folha_matricula', 'folha_lotacao',  'folha_instituicao', 'acrescimo_objetivo', 'usar_clientes_carteira','supervisor_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function tipo_usuario(){
        return $this->hasOne('App\TipoUsuario', 'id', 'tipo_usuario_id');
    }
    
    public function pedido_aprovador(){
        return $this->hasOne('App\AprovacaoDePedido', 'id', 'aprovador_id');
    }

    public function subordinados(){
        return $this->hasMany('App\User', 'responsavel', 'id')->orderby('codigo_representante');
    }

    public function supervisor(){
        return $this->hasOne('App\User', 'id', 'responsavel');
    }

    public function usuario_pedido_portal(){
        return $this->hasOne('App\PedidoPortal', 'usuario','id');
    }

    public function aprovador_pedido_portal(){
        return $this->hasOne('App\PedidoPortal', 'cod_usuario_autorizador', 'id');
    }

    public function camposSalvos(){
        return $this->hasMany('App\UserCamposSalvo', 'id', 'user_id');
    }

    public function codigoNasajon(){
        return $this->hasOne('App\UserNajason', 'usuario', 'codigo_nasajon');
    }

    public function vendedor_nasajon(){
        return $this->hasOne('App\VendedorNasajon', 'codigo', 'codigo_representante');
    }

    public function unidadeNegocioMetaUser(){
        return $this->hasMany('App\UnidadeNegocioMetaXUser', 'users_id', 'id');
    }

    public function detalhesRepresentanteCliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'codigo_representante');
    }

    public function detalhesRepresentanteFornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'codigo_representante');
    }

    public function detalhesModelHasRoles(){
        return $this->hasOne('App\ModelHasRole', 'model_id', 'id');
    }

    public function confirmacaoComissao(){
        
        return $this->hasMany('App\ComunicadoComissoe', 'vendedor_codigo', 'codigo_representante');
    }

    public function supervisorResponsavel(){
        return $this->hasOne('App\User', 'id', 'supervisor_id');
    }

    public function supervisorEquipe(){
        return $this->hasMany('App\User', 'supervisor_id', 'id');
    }

    public function unidadeNegocioMetaUserUltimo(){
        return $this->hasOne('App\UnidadeNegocioMetaXUser', 'users_id', 'id')->orderBy('id', 'desc');
    }
}
