<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClienteContatoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_clientes_contatos';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    public $primaryKey = 'cliente_id';

    protected $fillable = [
        'cliente_id', 'cliente_codigo', 'cliente_nome', 'contato_nome', 'contato_cargo', 'contato_ddd', 'contato_telefone', 'contato_email'
    ];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id','cliente_id');
    }
}
