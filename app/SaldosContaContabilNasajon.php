<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaldosContaContabilNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'nsview.vw_saldos_conta_contabil_265';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
    	'Classificação da Conta', 'Conta', 'Nome da Conta', 'Movimentação (Antes do Enc.)', 'Movimentação', 'Saldo', 'Saldo (Antes do Enc.)', 'Ano/Mês', 'Ano', 'Mês', 'Data', 'Empresa', 'Razão Social da Empresa', 'CNPJ da Empresa', 'Estabelecimento', 'Nome do Estabelecimento', 'CNPJ do Estabelecimento', 'Nível'
    ];
}
