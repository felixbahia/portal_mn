<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormaPagamentoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'ns.formaspagamentos';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'formapagamento';
    protected $keyType = 'string';

    public $guarded = ['formapagamento', 'codigo', 'descricao', 'tipo', 'padrao', 'uf', 'diasprevisaoreceber', 'tipodiasreceber', 'sinaldiasreceber', 'diasprevisaopagar', 'tipodiaspagar', 'sinaldiaspagar', 'lastupdate', 'grupoempresarial', 'bloqueada', 'tenant', 'utilizarinformacoesbancariasconfiguracaotitulo', 'desconto', 'juros', 'multa'];
}
