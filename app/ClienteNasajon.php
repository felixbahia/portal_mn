<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClienteNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_dados_clientes';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    public $primaryKey = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'codigo', 'nome', 'nomefantasia', 'cpf_cnpj', 'inscricaoestadual', 'telefones', 'email', 'emailcobranca', 'tipologradouro', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'ibge', 'uf', 'vendedor', 'cliente_desde', 'vendedor_codigo', 'bloqueado', 'indicadorinscricaoestadual', 'qualificacao', 'lastupdate', 'suframa_codigo', 'suframa_validade', 'suframa_desconto_habilitado'
    ];

    public function getCepFormatadoAttribute(){
        return str_replace('-','', $this->cep);
    }

    public function getQualificacaoDescricaoAttrobute(){
        $qualificacao_descriacao = [
            '0' => 'Pessoa Jurídica em Geral',
            '1' => 'Órgão, autarquia ou fundação da administração pública federal',
            '2' => 'Órgão, autarquia ou fundação da administração pública estadual',
            '3' => 'Órgão, autarquia ou fundação da administração pública municipal',
            '4' => 'Cooperativa de Crédito',
            '5' => 'Sociedade Cooperativa Agropecuária',
            '6' => 'Sociedade Cooperativa',
            '7' => 'Financeira',
            '8' => 'Soc. Seguradora, de Capitalização',
            '9' => 'Corretora Autonoma de Seguro',
            '10' => 'Entidade Aberta de Prev.Complementar',
            '11' => 'Entidade Fechada de Prev. Complementar',
            '12' => 'Sociedade de Economia Mista',
            '13' => 'Outras Entidades da Administração Pública Federal',
            '90' => 'Pessoa Física em Geral',
            '91' => 'Pessoa Agregada',
            '99' => 'Outros'
        ];
        return $qualificacao_descriacao[$this->qualificao];
    }

    public function getIndicadorInscricaoAttribute(){
        switch ($this->indicadorinscricaoestadual){
        case '1':
            return 'Contribuinte ICMS';
            break;
        case '2':
            return 'Contribuinte Isento';
            break;
        default:
            return 'Não contribuinte';
            break;
        }
    }

    public function pedido_portal(){
        return $this->hasMany('App\PedidoPortal', 'cod_cliente', 'codigo');
    }

    function estado_detalhe(){
        return $this->hasOne('App\CepEstado', 'uf', 'uf');
    }

    function endereco(){
        return $this->hasOne('App\CepEndereco', 'cep', 'cep_formatado');
    }

    function clientePrePago(){
        return $this->hasOne('App\ClientePrePago', 'cpf_cnpj', 'cpf_cnpj');
    }

    function notasVenda(){
        return $this->hasMany('App\NotasNasajon', 'cliente_documento', 'cpf_cnpj');
    }

    function ultimaVenda(){
        return $this->hasOne('App\NotasNasajon', 'cliente_documento', 'cpf_cnpj')->selectRaw('max(datasaida) as ultima_venda, cliente_documento')->groupBy('cliente_documento');
    }

    function representante(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }
    
    public function cliente_duvidoso(){
        return $this->hasOne('App\ClienteDuvidoso', 'cpf_cnpj','cpf_cnpj');
    }

    public function contrato(){
        return $this->hasOne('App\ContratoCliente', 'cpf_cnpj_cliente','cpf_cnpj');
    }
    
    public function contatos(){
        return $this->hasMany('App\ClienteContatoNasajon', 'cliente_id','id');
    }

    public function titulosEmAberto(){
        return $this->hasMany('App\TitulosEmAbertoNasajon', 'id_cliente', 'id');
    }

    public function blackList(){
        return $this->hasOne('App\ClienteBlackList', 'cpf_cnpj', 'cpf_cnpj');
    }

    public function devolucaos(){
        return $this->hasMany('App\DevolucaoNota', 'cliente_cpf_cnpj', 'cpf_cnpj');
    }

    public function clienteIndustrializacaoContaOrdem(){
        return $this->hasOne('App\ClienteTriangular', 'cpf_cnpj', 'cpf_cnpj');
    }
}
