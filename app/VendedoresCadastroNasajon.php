<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendedoresCadastroNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'ns.vwvendedores_tecidos_mn';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabelecimento_id', 
        'estabelecimento_codigo', 
        'conjunto_id', 
        'conjunto_codigo', 
        'vendedor_id', 
        'vendedor_pessoa', 
        'vendedor_nome', 
        'vendedor_inscricaomunicipal', 
        'vendedor_inscricaoestadual', 
        'vendedor_cnpj', 
        'vendedor_nomefantasia', 
        'vendedor_email', 
        'vendedor_observacao', 
        'vendedor_anotacao', 
        'vendedor_datacadastro', 
        'vendedor_grupoempresarial',
        'vendedor_bloqueado'
    ];
}
