<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserNajason extends Model
{
	protected $connection = 'nasajon';
    protected $table = 'ns.usuarios';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'usuario';
    protected $keyType = 'string';

	public $fillable = [ 'nome', 'situacao', 'email', 'login', 'senha', 'temresponsabilidadeatendimento', 'versao', 'moduloinicialpersona', 'moduloinicialscritta', 'moduloinicialcontabil', 'ultimoanocontabil', 'representante', 'departamento', 'ultimaempresapersona', 'ultimoestabelecimentocontabil', 'ultimaempresascritta', 'ultimogrupo', 'perfilusuario', 'usuario', 'grupodeusuario', 'ultimaentidadeempresarial_estoque', 'bloqueado_ate', 'telefone', 'representante_pessoa', 'vendedor', 'tenant', 'lastupdate', 'ultimoestabelecimentopersonaweb' ];
}
