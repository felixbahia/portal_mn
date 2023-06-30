<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LocalDeEstoqueEnderecoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.locaisdeestoquesenderecos';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'localdeestoqueendereco', 'localdeestoque', 'endereco', 'lastupdate', 'tenant', 'localdeestoqueendereco_pai', 'endereco_simplificado', 'endereco_nivel_item', 'analitico', 'estabelecimento', 'localdeestoqueorigem'
    ];

    public function estabelecimento_detalhe(){
        return $this->hasOne('App\NasajonEstabelecimento', 'estabelecimento', 'estabelecimento');
    }
}