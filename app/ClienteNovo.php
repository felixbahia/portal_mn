<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteNovo extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'ja_foi_cliente', 'ja_foi_cliente_quando', 'fisica_juridica', 'cpf_cnpj', 'inscricao_estadual', 'inscricao_municipal', 'nome_razao', 'guerra_apelido', 'telefone', 'telefone_fax', 'email', 'vendedor_codigo', 'transportador_codigo', 'forte_cliente', 'ramo_atividade', 'numero_filiais', 'numero_empregados', 'predio_proprio', 'aluguel', 'sucessora_de', 'ligacao_com', 'sugestao_credito', 'historico_cliente_praca', 'faturamento_cep', 'faturamento_logradouro', 'faturamento_numero', 'faturamento_complemento', 'faturamento_bairro', 'faturamento_cidade', 'faturamento_estado', 'faturamento_telefone', 'faturamento_telefone_fax', 'faturamento_email', 'cobranca_cep', 'cobranca_logradouro', 'cobranca_numero', 'cobranca_complemento', 'cobranca_bairro', 'cobranca_cidade', 'cobranca_estado', 'cobranca_telefone', 'cobranca_telefone_fax', 'cobranca_banco', 'cobranca_agencia', 'cobranca_conta', 'limite_credito', 'motivo_recusa', 'created_by', 'updated_by', 'deleted_by', 'tamanho_cliente', 'codigo_gerado', 'atividade', 'indicador_juros', 'conceitos_clientes_id', 'suframa','inscricao_estadual_indicador'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    public function status_descricao(){
        return $this->hasOne('App\StatusClienteNovo', 'id', 'status');
    }

    public function conceito(){
        return $this->hasOne('App\ConceitosCliente', 'id', 'conceitos_clientes_id');
    }

    public function vendedor(){
        return $this->hasOne('App\VendedorNasajon', 'codigo', 'vendedor_codigo');
    }

    public function transportador(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportador_codigo');
    }

    public function referencias(){
        return $this->hasMany(ClienteNovoReferencia::class);
    }

    public function socios(){
        return $this->hasMany(ClienteNovoSocio::class);
    }

    public function pedidos(){
        return $this->hasMany("App\PedidoPortal", "cod_cliente", "id");
    }

}
