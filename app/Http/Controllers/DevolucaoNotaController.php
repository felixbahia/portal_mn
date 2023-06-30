<?php

namespace App\Http\Controllers;

use App\DevolucaoNota;
use App\ClienteNasajon;
use App\NotaVendaNasajon;
use App\DevolucaoNotaMotivo;
use App\DevolucaoNotaProduto;
use App\DevolucaoNotaLog;
use App\DevolucaoNotaLogItens;
use App\DevolucaoNotaStatus;
use App\DevolucaoNotaMotivoStatus;
use App\NotasNasajon;
use App\User;
use App\NasajonEstabelecimento;
use App\PedidosVendaNasajon;
use App\FaturamentoNotaNasajon;
use App\NotasImportadasEntrada;
use App\DevolucaoNotasDocumento;
use App\Http\Controllers\EmailController;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Http\Requests\DevolucaoNotaSalvarNovoRequest;
use App\Http\Requests\DevolucaoNotaEditarRequest;

use Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;


class DevolucaoNotaController extends Controller
{
    public $storage = 'public/devolucao_nota';
    private $storage_files = 'public/devolucao_nota_documentos/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DevolucaoNota") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DevolucaoNota');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $status = DevolucaoNotaStatus::orderBy('id', 'asc')->get()->pluck('descricao', 'id');


        return view('programs.devolucao_notas.index')->with(['estabelecimentos' => $estabelecimentos, 'status' => $status]);
    }

    public function filter(Request $request){

        $fields = $request->only('estabelecimento', 'nota_fiscal', 'cliente', 'status');

        $query = DevolucaoNota::with('nota_nasajon', 'status_detalhes', 'cliente');

        if(isset($fields['nota_fiscal']) && !empty($fields['nota_fiscal'])){
            $query->where("nota_fiscal", $fields['nota_fiscal']);
        }

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $query->where("estabelecimento", str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $cliente = ClienteNasajon::where(DB::Raw("CONCAT(nome, ' - ', cpf_cnpj)"), 'ilike', '%' . $fields['cliente'] . '%')->get();

            $query->whereIn('cliente_cpf_cnpj', $cliente->pluck('cpf_cnpj'));
        }

        if(isset($fields['status']) && !empty($fields['status'])){
            if($fields['status'] == 11){
                $query->withTrashed()
                    ->where(function($query){
                        $query->whereNotNull('deleted_at')
                        ->orWhere('devolucao_nota_status_id', 11);
                    });
            }
            else{
                $query->where('devolucao_nota_status_id', $fields['status']);
            }
        }
        else{
            $query->where('devolucao_nota_status_id', '!=', 7);
        }

        $devolucoesObj = $query->get();

        $response = [];

        $estabelecimentos = returnEmpresasNasajonView();

        if(in_array(Auth::user()->tipo_usuario_id, [16, 12])){

            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            $users = User::
                where('id', Auth::id())
                ->whereNotNull('codigo_representante')
                ->first();

            $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                return $users->codigo_representante == $linha->nota_nasajon['revisao_vendedor_comissao']['vendedor_codigo'];
            });
            
        }

        $devolucoesObj->each(function($devolucao) use (&$response, $estabelecimentos){
            $linha = [];

            $id = Crypt::encrypt($devolucao->id);

            $linha['id_requisicao'] = $devolucao->id;
            $linha['id'] = $id;
            $linha['nota_fiscal'] = $devolucao->nota_fiscal;
            $linha['nota_id'] = $devolucao->nota_id;
            $linha['estabelecimento'] = $estabelecimentos[intval($devolucao->estabelecimento)];
            $linha['cliente'] = $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj;
            $linha['valor'] = $devolucao->valor;

            if(!empty($devolucao->motivo_devolucao)){
                $linha['motivo'] = $devolucao->motivo_devolucao->descricao;
            }
            else{
                $linha['motivo'] = '';
            }

            if(!is_null($devolucao->deleted_at)){
                $linha['status_exibir'] = 'Cancelado';
                $linha['status'] = 11;
            }
            else{
                $linha['status_exibir'] = $devolucao->status_detalhes->descricao;
                $linha['status'] = $devolucao->devolucao_nota_status_id;
            }

            if($devolucao->valor_parcial){
                $linha['valor_parcial'] = 'Parcial';
            }
            else{
                $linha['valor_parcial'] = 'Completo';
            }

            
            $response[] = $linha;
        });

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => [
                    'dados' => $response 
                ]
            ], 220
        );

    }

    public function modalNovo(){

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $motivos = [];

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::select()->get();

        $devolucaoNotaMotivoObj->each(function ($motivo) use(&$motivos){
            $motivos[$motivo->id] = $motivo['descricao']; 
        });

        $retorno = [];

        $retorno['estabelecimentos'] = $estabelecimentos;
        $retorno['motivos'] = $motivos;

        return view('programs.devolucao_notas.modal.novo')->with($retorno);

    }

    public function modalEditar(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::
            with([
                'nota_nasajon',
                'nota_nasajon.item',
                'nota_nasajon.faturamento',
                'produtos',
                'cliente',
                'documentos'
            ])
            ->find($id);
        
        $documentos_diversos = [];

        $documento_isento = [];

        if(!empty($devolucaoNotaObj->documentos)){
            foreach($devolucaoNotaObj->documentos as $documentos){
                if($documentos->tipo_documento != 'cliente_isento'){
                    $documentos_diversos[] =  [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'descricao' => $documentos->nome_arquivo,
                        'id_diversos' => encrypt($documentos->id)
                    ];
                }
                if($documentos->tipo_documento === 'cliente_isento'){
                    $documento_isento = [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'id_isento' => encrypt($documentos->id)
                    ];
                }
            }
        }

        $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
            ->whereDoesntHave('aprovadores', function($query){
                $query->where('devolucao_nota_status_id', 3);
            })
            ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
            ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
            ->where('id', '!=', $id)
            ->get();

        $notaNasajon = NotaVendaNasajon::with('faturamento_nota_devolucao', 'item')
            ->where('cod_estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
            ->where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($devolucaoNotaObj->nota_fiscal, 0))->first();

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $motivos = [];

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::whereHas('status')->get();

        $devolucaoNotaMotivoObj->each(function ($motivo) use(&$motivos){
            $motivos[$motivo->id] = $motivo['descricao']; 
        });

        $retorno = [];

        $retorno['documentos_diversos'] = $documentos_diversos;
        $retorno['documento_isento'] = $documento_isento;
        $retorno['estabelecimentos'] = $estabelecimentos;
        $retorno['motivos'] = $motivos;

        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['estabelecimento'] = intval($devolucaoNotaObj->estabelecimento);
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);
        $retorno['motivo'] = $devolucaoNotaObj->motivo;
        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;

        if(!is_null($devolucaoNotaObj->arquivo)){
            if(Storage::exists($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo)){
                $retorno['arquivo'] = Storage::url($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo);
            }
        }

        $retorno['observacao'] = $devolucaoNotaObj->observacao;

        if(in_array($devolucaoNotaObj->nota_nasajon->faturamento['Código da Operação'], ['VENDAORDEMTORO', 'VENDAAORDEM'])){
            $retorno['tipo_venda'] = 'Venda por conta e ordem';
        }
        else{
            $retorno['tipo_venda'] = 'Venda normal';
        }

        $retorno['motivo_reprovacao'] = $devolucaoNotaObj->motivo_reprovacao;

        $retorno['nome_contato'] = $devolucaoNotaObj->nome_contato;
        $retorno['telefone_contato'] = $devolucaoNotaObj->telefone_contato;
        $retorno['email_contato'] = $devolucaoNotaObj->email_contato;
        
        if(!empty($devolucaoNotaObj->laudo_imagem)){
            if(Storage::exists($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem)){
                $retorno['laudo_tecnico'] = Storage::url($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem);
            }
        }

        $retorno['produtos'] = [];
        
        $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use (&$retorno, $devolucaoNotaObj, $outrasDevolucoesNotasObj){

            $solicitado = $devolucaoNotaObj->produtos->firstWhere('produto_id', $item->id_item_nota);
            $linha = [];

            $linha['id'] = $item->id_item_nota;
            $linha['codigo'] = $item->produto_detalhes->codigo_produto;
            $linha['grupo'] = $item->produto_detalhes->grupo;
            $linha['descricao'] = $item->produto_detalhes->descricao;

            if(!is_null($devolucaoNotaObj->faturamento_nota_devolucao)){
                $devolvidosNasajon = $devolucaoNotaObj->faturamento_nota_devolucao
                    ->find('TIPO', 'DEVOLUÇÃO')
                    ->pluck('itens_faturamento')
                    ->where('Item - Código', $item->produto_detalhes->codigo_produto)
                    ->sum('Item - Quantidade');
            }
            else{
                $devolvidosNasajon = 0;
            }

            $devolvidosPendentes = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota)->where('quantidade_recebida', '!=', null)->sum('quantidade');
            $linha['quantidade'] = parserValor($item->quantidade - $devolvidosPendentes - $devolvidosNasajon);

            if($linha['quantidade'] <= 0){
                return;
            }

            if(!empty($solicitado)){
                $linha['quantidade_devolvida'] = parserValor($solicitado->quantidade);
            }
            else{
                $linha['quantidade_devolvida'] = '';
            }

            $retorno['produtos'][] = $linha;
        });

        return view('programs.devolucao_notas.modal.editar')->with($retorno);

    }

    public function visualizarModal(Request $request){
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::withTrashed()
            ->with(
                'produtos',
                'produtos.produto_na_nota',
                'produtos.produto_na_nota.produto_detalhes',
                'nota_nasajon',
                'nota_remessa_nasajon',
                'motivo_devolucao',
                'status_detalhes',
                'aprovadores',
                'aprovadores.status',
                'aprovadores.aprovador_detalhes',
                'createdby',
                'updatedby',
                'deletedby',
                'cliente',
                'titulosDescontados',
                'titulosCancelados',
                'documentos',
                'nota_nasajon',
                'nota_remessa_nasajon'
            )
            ->find($id);

        $estabelecimentos = returnEmpresasNasajonView();

        $responsabilidade_frete = [
            'textil' => 'MN Têxtil',
            'cliente' => 'Cliente',
            'representante' => 'Representante'
        ];

        $retorno = [];
        $documentos_diversos = [];

        $documento_isento = null;



        if(!empty($devolucaoNotaObj->documentos)){
            foreach($devolucaoNotaObj->documentos as $documentos){

                $verificar_transportadora = false;
                if(!empty($devolucaoNotaObj->transportador_email) && $devolucaoNotaObj->pedido->estabelecimento_codigo == '03' && $documentos->tipo_documento == 'nf_remessa' ||
                !empty($devolucaoNotaObj->transportador_email) && $devolucaoNotaObj->pedido->estabelecimento_codigo == '04' && $documentos->tipo_documento == 'nf_remessa' ||
                !empty($devolucaoNotaObj->transportador_email) && $devolucaoNotaObj->pedido->estabelecimento_codigo == '03' && $documentos->tipo_documento == 'carta_correcao_remessa' ||
                !empty($devolucaoNotaObj->transportador_email) && $devolucaoNotaObj->pedido->estabelecimento_codigo == '04' && $documentos->tipo_documento == 'carta_correcao_remessa'){
                    $verificar_transportadora = true;
                }

                if($documentos->tipo_documento != 'cliente_isento'){
                    $documentos_diversos[] =  [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'descricao' => $documentos->nome_arquivo,
                        'id' => encrypt($documentos->id),
                        'verifica_email' => $verificar_transportadora 
                    ];
                }
                if($documentos->tipo_documento === 'cliente_isento'){
                    $documento_isento = Storage::url($this->storage_files . $documentos->caminho);
                }
            }
        }
        
        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['codigo_estabelecimento'] = $devolucaoNotaObj->estabelecimento;

        $id = Crypt::encrypt($devolucaoNotaObj->id);

        $retorno['id'] = $id;
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['nota_id'] = $devolucaoNotaObj->nota_id;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);

        if($devolucaoNotaObj->valor_parcial){
            $retorno['tipo_devolucao'] = 'Parcial';
        }
        else{
            $retorno['tipo_devolucao'] = 'Completa';
        }

        $retorno['aprovadores'] = [];

        $retorno['nota_cliente_numero'] = $devolucaoNotaObj->nota_cliente_numero;

        $devolucaoNotaObj->aprovadores->each( function ($aprovador) use(&$retorno){
            
            $linha = [];

            $linha['status'] = (!empty($aprovador->status->descricao)) ? $aprovador->status->descricao : '';
            $linha['aprovador'] = (!empty($aprovador->aprovador_detalhes->name)) ? $aprovador->aprovador_detalhes->name : '';
            $linha['data'] = $aprovador->created_at->format('d/m/Y H:i:s');

            $retorno['aprovadores'][] = $linha;

        });

        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;
        $retorno['valor_devolvido'] = parserValor($devolucaoNotaObj->valor);
        $retorno['motivo'] = $devolucaoNotaObj->motivo_devolucao->descricao; 
        $retorno['motivo_reprovacao'] = $devolucaoNotaObj->motivo_reprovacao;

        if(isset($devolucaoNotaObj->nota_remessa_nasajon) && !empty($devolucaoNotaObj->nota_remessa_nasajon)){
            $retorno['nota_remessa_id'] = $devolucaoNotaObj->nota_remessa_nasajon->id;
            $retorno['nota_remessa'] = $devolucaoNotaObj->nota_remessa_nasajon->numero;
        }

        if(!empty($devolucaoNotaObj->laudo_imagem)){
            if(Storage::exists($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem)){
                $retorno['laudo_tecnico'] = Storage::url($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem);
            }
        }

        if(!is_null($devolucaoNotaObj->arquivo)){
            if(Storage::exists($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo)){
                $retorno['arquivo'] = Storage::url($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->nota_cliente_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo)){
                $retorno['nota_cliente'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->romaneio_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo)){
                $retorno['romaneio_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->deleted_at)){
            $retorno['status'] = 'Cancelado';
        }
        else{
            $retorno['status'] = $devolucaoNotaObj->status_detalhes->descricao;

            if($devolucaoNotaObj->devolucao_nota_status_id == 2){
                $retorno['motivo_reprovacao'] = $devolucaoNotaObj->motivo_reprovacao;
            }
        }

        if(!empty($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['responsabilidade_frete'] = $responsabilidade_frete[$devolucaoNotaObj->responsabilidade_frete];
        }
        else{
            $retorno['responsabilidade_frete'] = '';
        }

        if(!empty($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['frete_valor'] = parserValor($devolucaoNotaObj->frete_valor);
        }
        else{
            $retorno['frete_valor'] = '';
        }

        if(!empty($devolucaoNotaObj->aprovador)){
            $retorno['aprovador'] = $devolucaoNotaObj->aprovador_detalhes->name;
        }

        $retorno['criado_por'] = $devolucaoNotaObj->createdby->name;
        $retorno['criado_em'] = $devolucaoNotaObj->created_at->format('d/m/Y H:i:s');

        if(!empty($devolucaoNotaObj->updated_by)){
            $retorno['modificado_por'] = (!empty($devolucaoNotaObj->updatedby->name)) ? $devolucaoNotaObj->updatedby->name : '';
            $retorno['modificado_em'] = (!empty($devolucaoNotaObj->updated_at)) ? $devolucaoNotaObj->updated_at->format('d/m/Y H:i:s') : '';
        }

        if(!empty($devolucaoNotaObj->deleted_by) && !empty($devolucaoNotaObj->deleted_at)){
            $retorno['excluido_por'] = $devolucaoNotaObj->deletedby->name;
            $retorno['excluido_em'] = $devolucaoNotaObj->deleted_at->format('d/m/Y H:i:s');
        }

        $retorno['observacao'] = $devolucaoNotaObj->observacao;

        $retorno['produtos'] = [];

        $retorno['mostrar_quantidades_devolvidas'] = false;

        if($devolucaoNotaObj->aprovadores->where('devolucao_nota_status_id', 4)->isNotEmpty() || $devolucaoNotaObj->devolucao_nota_status_id == 5){
            $retorno['mostrar_quantidades_devolvidas'] = true;
        }

        if(!is_null($devolucaoNotaObj->produtos)){
            $devolucaoNotaObj->produtos->each(function ($produto) use(&$retorno){

                $linha = [];

                $linha['codigo_produto'] = $produto->produto_na_nota->produto_detalhes->codigo_produto;
                $linha['grupo'] = $produto->produto_na_nota->produto_detalhes->grupo;
                $linha['descricao'] = $produto->produto_na_nota->produto_detalhes->descricao;
                $linha['quantidade'] = parserValor($produto->quantidade);
                $linha['quantidade_recebida'] = parserValor($produto->quantidade_recebida);

                $retorno['produtos'][] = $linha;

            });
        }

        $titulos_descontados = [];
        if(!empty($devolucaoNotaObj->titulosDescontados)){
            foreach($devolucaoNotaObj->titulosDescontados as $titulo_descontado){
               $saldo = $titulo_descontado->titulo_valor - $titulo_descontado->desconto_valor;

               if($saldo>0){
                   $saldo = parserValor($titulo_descontado->titulo_valor - $titulo_descontado->desconto_valor);                
                }else{
                    $saldo = ' '; 
                }

                $titulos_descontados[] = [
                    'titulo_numero' => $titulo_descontado->titulo_numero,
                    'titulo_valor' => parserValor($titulo_descontado->titulo_valor),
                    'desconto_valor' => parserValor($titulo_descontado->desconto_valor),
                    'data_emissao' => $titulo_descontado->data_emissao->format('d/m/Y'),
                    'data_vencimento' => $titulo_descontado->data_vencimento->format('d/m/Y'),
                    'saldo_valor' => $saldo,
                ];
            }
        }
        $retorno['titulos_descontados'] = $titulos_descontados;

        $titulos_cancelados = [];
        if(!empty($devolucaoNotaObj->titulosCancelados)){
            foreach($devolucaoNotaObj->titulosCancelados as $titulo_cancelado){
                $titulos_cancelados[] = [
                    'titulo_numero' => $titulo_cancelado->titulo_numero,
                    'titulo_valor' => parserValor($titulo_cancelado->titulo_valor),
                    'data_emissao' => $titulo_cancelado->data_emissao->format('d/m/Y'),
                    'data_vencimento' => $titulo_cancelado->data_vencimento->format('d/m/Y'),
                ];
            }
        }
        $retorno['titulos_cancelados'] = $titulos_cancelados;

        $titulos_creditos = [];

        if(!empty($devolucaoNotaObj->titulo_credito_uuid_nasajon)){
            $titulos_creditos[] = [
                'titulo_numero' => $devolucaoNotaObj->titulo_credito_numero,
                'titulo_valor' => parserValor($devolucaoNotaObj->titulo_credito_valor),
            ];
        }

        $retorno['titulos_creditos'] = $titulos_creditos;
        $retorno['documentos_diversos'] = $documentos_diversos;
        $retorno['documento_isento'] = $documento_isento;

        return view('programs.devolucao_notas.modal.visualizar')->with($retorno);
    }

    public function modalExcluir(Request $request){
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::with('nota_nasajon', 'cliente')->find($id);

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $motivos = [];

        $retorno = [];

        $retorno['estabelecimentos'] = $estabelecimentos;
        $retorno['motivos'] = $motivos;

        $id = Crypt::encrypt($devolucaoNotaObj->id);

        $retorno['id'] = $id;
        $retorno['estabelecimento'] = intval($devolucaoNotaObj->estabelecimento);
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);
        $retorno['motivo'] = $devolucaoNotaObj->motivo_devolucao->descricao;
        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;
        $retorno['valor_devolvido'] = parserValor($devolucaoNotaObj->valor);

        $retorno['motivo_reprovacao'] = $devolucaoNotaObj->motivo_reprovacao;

        return view('programs.devolucao_notas.modal.excluir')->with($retorno);
    }

    public function modalLogs(Request $request){
        
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaLogsObj = DevolucaoNotaLog::with('usuario_detalhes', 'nota_antiga', 'nota_nova', 'motivo_antigo_detalhes', 'motivo_novo_detalhes', 'status_antigo_detalhes', 'status_novo_detalhes', 'itens', 'itens.produto')
            ->where('devolucao_nota_id', $id)->orderBy('created_at', 'desc')->get();

        $retorno = [];

        $devolucaoNotaLogsObj->each(function ($log) use (&$retorno){

            $linha = [];

            $linha['data'] = $log->created_at->format('d/m/Y H:i:s');
            $linha['acao'] = $log->acao;
            $linha['mensagem'] = $log->mensagem;

            foreach([
                'nota_id' => 'Nota',
                'nome_contato' => 'Nome do contato',
                'telefone_contato' => 'Telefone de contato',
                'email_contato' => 'Email de contato',
                'motivo' => 'Motivo',
                'status' => 'Status',
                'valor' => 'Valor',
                'valor_parcial' => 'Valor parcial',
                'observacao' => 'Observação',
                'arquivo' => 'Arquivo'
            ] as $campo => $nome){
                if(!empty($log[$campo . '_antigo']) || !empty($log[$campo . '_novo'])){

                    if($campo == 'nota_id'){
                        
                        $linha['mensagem'] .= '<li>Nota: ';
                        
                        if(!empty($log[$campo . '_antigo'])){
                            $linha['mensagem'] .= 'Mudada de "' . $log->nota_antiga->numero.'-'.$log->nota_antiga->serie . '" para "' . $log->nota_nova->numero.'-'.$log->nota_nova->serie;
                        }
                        else{
                            $linha['mensagem'] .= $log->nota_nova->numero.'-'.$log->nota_nova->serie;
                        }
                    }
                    else if($campo == 'motivo'){
                        
                        $linha['mensagem'] .= '<li>Motivo: ';
                        
                        if(!empty($log[$campo . '_antigo'])){
                            $linha['mensagem'] .= 'Mudado de "' . $log->motivo_antigo_detalhes->descricao . '" para "' . $log->motivo_novo_detalhes->descricao;
                        }
                        else{
                            $linha['mensagem'] .= $log->motivo_novo_detalhes->descricao;
                        }
                    }
                    else if($campo == 'status'){
                        
                        $linha['mensagem'] .= '<li>Status: ';
                        
                        if(!empty($log[$campo . '_antigo'])){
                            $linha['mensagem'] .= 'Mudado de "' . $log->status_antigo_detalhes->descricao . '" para "' . $log->status_novo_detalhes->descricao . '"';
                        }
                        else{
                            $linha['mensagem'] .= $log->status_novo_detalhes->descricao;
                        }
                    }
                    else if($campo == 'valor_parcial'){
                        $linha['mensagem'] .= '<li>Tipo de devolução: ';

                        if($log->valor_parcial_novo !== $log->valor_parcial_antigo && !is_null($log->valor_parcial_antigo)){
                            $linha['mensagem'] .= 'mudada de ' . ($log->valor_parcial_antigo?'Parcial':'Completa') . ' para ';
                        }
                            
                        if($log->valor_parcial_novo === true){
                            $linha['mensagem'] .= 'Parcial <ul>';

                            $log->itens->each(function($item) use (&$linha){
                                $linha['mensagem'] .= '<li>Produto: ' . $item->codigo_produto . ' - ' . $item->produto->descricao . ' - Quantidade: ' . parserValor($item->quantidade) . '</li>';
                            });

                            $linha['mensagem'] .= '</ul>';

                        }
                        else{
                            $linha['mensagem'] .= 'Completa';
                        }

                        
                    }
                    else if($campo == 'valor'){
                        $linha['mensagem'] .= "<li>" . $nome . ': ';
                        
                        if(!empty($log[$campo . '_antigo'])){
                            $linha['mensagem'] .= 'Mudado de ' . parserValor($log[$campo . '_antigo']) . ' para ' . parserValor($log[$campo . '_novo']);
                        }
                        else{
                            $linha['mensagem'] .= parserValor($log[$campo . '_novo']);
                        }
                    }
                    else{
                        $linha['mensagem'] .= "<li>" . $nome . ': ';
                        
                        if(!empty($log[$campo . '_antigo'])){
                            $linha['mensagem'] .= 'Mudado de "' . $log[$campo . '_antigo'] . '" para "' . $log[$campo . '_novo'] .'"';
                        }
                        else{
                            $linha['mensagem'] .= $log[$campo . '_novo'];
                        }
                    }
                    $linha['mensagem'] .= '</li>';
                }
                else if($campo == 'arquivo' && $log['arquivo'] === true){
                    $linha['mensagem'] .= '<li>Enviado novo arquivo do tecido</li>';   
                }
            }

            $linha['usuario'] = $log->usuario_detalhes->name;

            $retorno[] = $linha;

        });

        ksort($retorno);
        
        return view('programs.devolucao_notas.modal.log')->with(['logs' => $retorno]);

    }

    public function salvarNovo(DevolucaoNotaSalvarNovoRequest $request){
        $fields = $request->only('arquivo_cliente_isento','descricao_documento','documento','estabelecimento', 'nota_fiscal', 'motivo', 'valor_parcial', 'nome_contato', 'telefone_contato', 'email_contato', 'produtos', 'observacao');

        $notaNasajonObj = NotaVendaNasajon::with('item', 'faturamento_nota_devolucao','transportadora')->where('cod_estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($fields['nota_fiscal'], 0))
            ->first();

        $devolucaoNotaObj = new DevolucaoNota;

        $devolucaoNotaObj->nota_id = $notaNasajonObj->id_nota;
        $devolucaoNotaObj->nota_fiscal = $notaNasajonObj->numero;
        $devolucaoNotaObj->serie = $notaNasajonObj->serie;
        $devolucaoNotaObj->data_emissao = $notaNasajonObj->emissao;
        $devolucaoNotaObj->estabelecimento = $notaNasajonObj->cod_estabelecimento;
        $devolucaoNotaObj->cliente_cpf_cnpj = $notaNasajonObj->documento_cliente;
        $devolucaoNotaObj->cliente_razao_social = $notaNasajonObj->nome_cliente;

        $devolucaoNotaObj->nome_contato = $request['nome_contato'];
        $devolucaoNotaObj->telefone_contato = $request['telefone_contato'];
        $devolucaoNotaObj->email_contato = $request['email_contato'];
        $devolucaoNotaObj->observacao = $request['observacao'];
        $devolucaoNotaObj->motivo = $fields['motivo'];

        $devolucaoNotaMotivoStatusObj = DevolucaoNotaMotivoStatus::where('devolucao_nota_motivo_id', $fields['motivo'])
            ->where('ordem', 1)->first();
        
        $devolucaoNotaObj->devolucao_nota_status_id = $devolucaoNotaMotivoStatusObj->devolucao_nota_status_id;

        $devolucaoNotaObj->created_by = Auth::user()->id;

        $devolucaoNotaObj->save();

        if(isset($fields['valor_parcial']) && $fields['valor_parcial'] == 1){
            $devolucaoNotaObj->valor_parcial = true;

            $devolucaoNotaObj->valor = 0;

            foreach($fields['produtos'] as $key => $value){
                $produto = new DevolucaoNotaProduto;

                $item = $notaNasajonObj->item->firstWhere('id_item_nota', $value['produto']);

                $produto->produto_id = $value['produto'];
                $produto->quantidade = parserNumber($value['quantidade_devolvida']);
                $produto->created_by = Auth::user()->id;

                $valor_total_item = $item->valor_unitario * parserNumber($value['quantidade_devolvida']);

                if(!empty($item->aliquota_ipi)){
                    $valor_total_item =  $valor_total_item + ($valor_total_item * $item->aliquota_ipi / 100);
                }

                $devolucaoNotaObj->valor += $valor_total_item;

                $devolucaoNotaObj->produtos()->save($produto);
            }
        }
        else{
            $devolucaoNotaObj->valor = $notaNasajonObj->valor;
        }

        $devolucaoNotaObj->save();

        if($request->hasFile('arquivo')){
            $devolucaoNotaObj->arquivo = 'tecido_imagem_' . $devolucaoNotaObj->id . '.' . $request->file('arquivo')->extension();
            $request->file('arquivo')->storeAs($this->storage . '/imagem', $devolucaoNotaObj->arquivo);
            $devolucaoNotaObj->save();
        }

        $documentos = $request->file('documento');
        $mensagem_sem_transportadora = '';
        $documento_isento = $request->file('arquivo_cliente_isento');

        if($request->hasFile('documento'))
        {
            foreach($documentos as $key => $documento){
                $dococumentos_devolucao = new DevolucaoNotasDocumento;
                $dococumentos_devolucao->devolucao_nota_id = $devolucaoNotaObj->id;
                $dococumentos_devolucao->tipo_documento = 'diversos';
                $dococumentos_devolucao->nome_arquivo = $fields['descricao_documento'][$key];
                $dococumentos_devolucao->created_by = Auth::user()->id;
                $dococumentos_devolucao->save();
                $file = $dococumentos_devolucao->id.'.' .$documento->getClientOriginalExtension();
                $dococumentos_devolucao->caminho = $file;
                $documento->storeAs($this->storage_files, $file);
                $dococumentos_devolucao->save();
                
            }
        }

        if($request->hasFile('arquivo_cliente_isento'))
        {
            $dococumento_isento = new DevolucaoNotasDocumento;
            $dococumento_isento->devolucao_nota_id = $devolucaoNotaObj->id;
            $dococumento_isento->tipo_documento = 'cliente_isento';
            $dococumento_isento->nome_arquivo = 'cliente isento '.$devolucaoNotaObj->id;
            $dococumento_isento->created_by = Auth::user()->id;
            $dococumento_isento->save();
            $file = $dococumento_isento->id.'.' .$documento_isento->getClientOriginalExtension();
            $dococumento_isento->caminho = $file;
            $documento_isento->storeAs($this->storage_files, $file);
            $dococumento_isento->save();
        }

        $devolucaoNotaLogObj = new DevolucaoNotaLog;

        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Criação de requisição de devolução';
        $devolucaoNotaLogObj->mensagem = 'Valores inseridos';
        $devolucaoNotaLogObj->usuario = Auth::id();
        $devolucaoNotaLogObj->nota_id_novo = $devolucaoNotaObj->nota_id;
        $devolucaoNotaLogObj->nome_contato_novo = $devolucaoNotaObj->nome_contato;
        $devolucaoNotaLogObj->telefone_contato_novo = $devolucaoNotaObj->telefone_contato;
        $devolucaoNotaLogObj->email_contato_novo = $devolucaoNotaObj->email_contato;
        $devolucaoNotaLogObj->motivo_novo = $devolucaoNotaObj->motivo;
        $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;
        $devolucaoNotaLogObj->valor_parcial_novo = $devolucaoNotaObj->valor_parcial?true:false;
        $devolucaoNotaLogObj->valor_novo = $devolucaoNotaObj->valor;
        $devolucaoNotaLogObj->observacao_novo = $devolucaoNotaObj->observacao;
        $devolucaoNotaLogObj->arquivo = $request->hasFile('arquivo')?true:false;

        $devolucaoNotaLogObj->save();

        if($devolucaoNotaObj->valor_parcial){

            $devolucaoNotaObj->load('produtos.produto_na_nota', 'produtos.produto_na_nota.produto_detalhes');

            $devolucaoNotaObj->produtos->each(function ($produto) use($devolucaoNotaLogObj){
                $devolucaoNotaLogItensObj = new DevolucaoNotaLogItens;

                $devolucaoNotaLogItensObj->devolucao_nota_log_id = $devolucaoNotaLogObj->id;
                $devolucaoNotaLogItensObj->codigo_produto = $produto->produto_na_nota->produto_detalhes->codigo_produto;
                $devolucaoNotaLogItensObj->quantidade = $produto->quantidade;

                $devolucaoNotaLogItensObj->save();
            });
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!'.$mensagem_sem_transportadora,
                'error' => [],
                'response' => []
            ], 220
        );

    }

    public function salvarEdicao(DevolucaoNotaEditarRequest $request){

        $fields = $request->only('id', 'arquivo_cliente_isento','descricao_documento','documento','estabelecimento', 'nota_fiscal', 'motivo', 'valor_parcial', 'nome_contato', 'telefone_contato', 'email_contato', 'produtos', 'observacao');
        
        $notaNasajonObj = NotaVendaNasajon::where('cod_estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where('numero', $fields['nota_fiscal'])
            ->with('transportadora')->first();

        $id = Crypt::decrypt($fields['id']);

        $devolucaoNotaObj = DevolucaoNota::find($id);

        $original = $devolucaoNotaObj->getOriginal();     

        $devolucaoNotaObj->nota_id = $notaNasajonObj->id_nota;
        $devolucaoNotaObj->nota_fiscal = $notaNasajonObj->numero;
        $devolucaoNotaObj->serie = $notaNasajonObj->serie;
        $devolucaoNotaObj->data_emissao = $notaNasajonObj->emissao;
        $devolucaoNotaObj->estabelecimento = $notaNasajonObj->cod_estabelecimento;
        $devolucaoNotaObj->cliente_cpf_cnpj = $notaNasajonObj->documento_cliente;
        $devolucaoNotaObj->cliente_razao_social = $notaNasajonObj->nome_cliente;
        
        $devolucaoNotaObj->nome_contato = $request['nome_contato'];
        $devolucaoNotaObj->telefone_contato = $request['telefone_contato'];
        $devolucaoNotaObj->email_contato = $request['email_contato'];
        $devolucaoNotaObj->observacao = $request['observacao'];
        $devolucaoNotaObj->motivo = $fields['motivo'];

        $devolucaoNotaMotivoStatusObj = DevolucaoNotaMotivoStatus::where('devolucao_nota_motivo_id', $fields['motivo'])
            ->where('ordem', 1)->first();

        $documentos = $request->file('documento');

        $documento_isento = $request->file('arquivo_cliente_isento');
        $mensagem_sem_transportadora = '';

        if($request->hasFile('documento'))
        {
            foreach($documentos as $key => $documento){
                $dococumentos_devolucao = new DevolucaoNotasDocumento;
                $dococumentos_devolucao->devolucao_nota_id = $devolucaoNotaObj->id;
                $dococumentos_devolucao->tipo_documento = 'diversos';
                $dococumentos_devolucao->nome_arquivo = $fields['descricao_documento'][$key];
                $dococumentos_devolucao->created_by = Auth::user()->id;
                $dococumentos_devolucao->save();
                $file = $dococumentos_devolucao->id.'.' .$documento->getClientOriginalExtension();
                $dococumentos_devolucao->caminho = $file;
                $documento->storeAs($this->storage_files, $file);
                $dococumentos_devolucao->save();
            }
        }
        
        if($request->hasFile('arquivo_cliente_isento'))
        {
            $dococumento_isento = new DevolucaoNotasDocumento;
            $dococumento_isento->devolucao_nota_id = $devolucaoNotaObj->id;
            $dococumento_isento->tipo_documento = 'cliente_isento';
            $dococumento_isento->nome_arquivo = 'cliente isento '.$devolucaoNotaObj->id;
            $dococumento_isento->created_by = Auth::user()->id;
            $dococumento_isento->save();
            $file = $dococumento_isento->id.'.' .$documento_isento->getClientOriginalExtension();
            $dococumento_isento->caminho = $file;
            $documento_isento->storeAs($this->storage_files, $file);
            $dococumento_isento->save();
        }
        
        $devolucaoNotaObj->devolucao_nota_status_id = $devolucaoNotaMotivoStatusObj->devolucao_nota_status_id;

        $devolucaoNotaObj->updated_by = Auth::user()->id;
        $devolucaoNotaObj->valor_parcial = false;

        DevolucaoNotaProduto::where('devolucao_nota_id', $devolucaoNotaObj->id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => Auth::user()->id
            ]);

        if(isset($fields['valor_parcial']) && $fields['valor_parcial'] == 1){
            $devolucaoNotaObj->valor_parcial = true;

            $devolucaoNotaObj->valor = 0;

            $verifica_produto = DevolucaoNotaProduto::where('devolucao_nota_id',$devolucaoNotaObj->id)
            ->delete();
            
            foreach($fields['produtos'] as $key => $value){

                $produto = new DevolucaoNotaProduto;

                $item = $notaNasajonObj->item->firstWhere('id_item_nota', $value['produto']);

                $produto->produto_id = $value['produto'];
                $produto->quantidade = parserNumber($value['quantidade_devolvida']);
                $produto->created_by = Auth::user()->id;

                $valor_total_item = $item->valor_unitario * parserNumber($value['quantidade_devolvida']);

                if(!empty($item->aliquota_ipi)){
                    $valor_total_item =  $valor_total_item + ($valor_total_item * $item->aliquota_ipi / 100);
                }

                $devolucaoNotaObj->valor += $valor_total_item;

                $devolucaoNotaObj->produtos()->save($produto);
            }
        }
        else{
            $devolucaoNotaObj->valor = $notaNasajonObj->valor;
        }

        if($request->hasFile('arquivo')){
            $devolucaoNotaObj->arquivo = 'tecido_imagem_' . $devolucaoNotaObj->id . '.' . $request->file('arquivo')->extension();
            $request->file('arquivo')->storeAs($this->storage . '/imagem', $devolucaoNotaObj->arquivo);
        }
    
        $devolucaoNotaObj->save();

        $devolucaoNotaLogObj = new DevolucaoNotaLog;

        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Edição da requisição em aberto';
        $devolucaoNotaLogObj->mensagem = 'Valores editados';
        $devolucaoNotaLogObj->usuario = Auth::id();

        $mudancas = $devolucaoNotaObj->getChanges();

        $keys = [
            'nota_id',
            'nome_contato',
            'telefone_contato',
            'email_contato',
            'motivo',
            'status',
            'valor',
            'observacao'
        ]; 

        foreach(array_intersect(\array_keys($mudancas), $keys) as $campo){
            if($devolucaoNotaLogObj[$campo] != $mudancas[$campo]){
                $devolucaoNotaLogObj[$campo . '_antigo'] = $original[$campo];
                $devolucaoNotaLogObj[$campo . '_novo'] = $mudancas[$campo];
            }
        }

        $devolucaoNotaLogObj->save();

        if($devolucaoNotaObj->valor_parcial){

            $devolucaoNotaLogObj->valor_parcial_antigo = $original['valor_parcial'];
            $devolucaoNotaLogObj->valor_parcial_novo = true;

            $devolucaoNotaLogObj->save();

            $devolucaoNotaObj->load('produtos.produto_na_nota', 'produtos.produto_na_nota.produto_detalhes');

            $devolucaoNotaObj->produtos->each(function ($produto) use($devolucaoNotaLogObj, $request){
                $devolucaoNotaLogItensObj = new DevolucaoNotaLogItens;

                $devolucaoNotaLogItensObj->devolucao_nota_log_id = $devolucaoNotaLogObj->id;
                $devolucaoNotaLogItensObj->codigo_produto = $produto->produto_na_nota->produto_detalhes->codigo_produto;
                $devolucaoNotaLogItensObj->quantidade = $produto->quantidade;
                
                $devolucaoNotaLogItensObj->save();
            });
        }
        else{
            $devolucaoNotaLogObj->valor_parcial_antigo = $original['valor_parcial'];
            $devolucaoNotaLogObj->valor_parcial_novo = false;
            $devolucaoNotaLogObj->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!'.$mensagem_sem_transportadora,
                'error' => [],
                'response' => []
            ], 220
        );

    }

    public function excluir(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::find($id);

        $devolucaoNotaObj->deleted_by = Auth::user()->id;
        $devolucaoNotaObj->save();
        $devolucaoNotaObj->delete();

        $devolucaoNotaProduto = DevolucaoNotaProduto::where('devolucao_nota_id', $id)->first();
        $devolucaoNotaProduto->deleted_by = Auth::id();
        $devolucaoNotaProduto->save();
        $devolucaoNotaProduto->delete();

        $devolucaoNotaLogObj = new DevolucaoNotaLog;

        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Cancelamento';
        $devolucaoNotaLogObj->mensagem = 'Requisição excluída pelo usuário';
        $devolucaoNotaLogObj->usuario = Auth::id();

        $devolucaoNotaLogObj;

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Requisição excluída com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );

    }

    public function recuperarNota(Request $request){

        $fields = $request->only('estabelecimento', 'nota_fiscal', 'id');

        $notaVendaNasajonQuery = NotaVendaNasajon::with('faturamento', 'faturamento_nota_devolucao', 'faturamento_nota_devolucao.itens_faturamento', 'item', 'item.produto_detalhes')
            ->select('documento_cliente', 'nome_cliente', 'emissao', 'valor', 'id_nota')
            ->where('cod_estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($fields['nota_fiscal'], 0));

        if(Auth::user()->tipo_usuario_id == 12){
            $notaVendaNasajonQuery->whereHas('revisao_vendedor_comissao', function($query){
                $query->where('vendedor_codigo', Auth::user()->codigo_representante);
            });
        }
            
        $notaVendaNasajonObj = $notaVendaNasajonQuery->first();

        if(empty($notaVendaNasajonObj)){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Nota não encontrada',
                    'error' => [
                        'nota_fiscal' => 'Nota não encontrada'
                    ],
                    'response' => []
                ], 422
            );
        }

        $retorno = [];

        $raiz_cnpj = str_replace('.', '', explode('/', $notaVendaNasajonObj->documento_cliente)[0]);
        
        if(in_array($raiz_cnpj, ['06311274', '05075884'])){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Não é permitida devolução de notas intercompany',
                    'error' => [
                        'nota_fiscal' => 'Não é permitida devolução de notas intercompany'
                    ],
                    'response' => []
                ], 422
            );
        }

        /* retirada temporária da regra 

        if(Carbon::now()->diffInDays($notaVendaNasajonObj->emissao) > 31 && !in_array(Auth::user()->tipo_usuario_id, [1, 15, 18])){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Não é permitida a devolução de notas emitidas há 32 dias ou mais',
                    'error' => [
                        'nota_fiscal' => 'Não é permitida a devolução de notas emitidas há 32 dias ou mais'
                    ],
                    'response' => []
                ], 422
            );
        }
        */

        $retorno['cliente'] = $notaVendaNasajonObj->nome_cliente . ' - ' . $notaVendaNasajonObj->documento_cliente;
        $retorno['valor'] = parserValor($notaVendaNasajonObj->valor);
        $retorno['emissao'] = parserData($notaVendaNasajonObj->emissao);

        if(in_array($notaVendaNasajonObj->faturamento['Código da Operação'], ['VENDAORDEMTORO', 'VENDAAORDEM'])){
            $retorno['tipo_venda'] = 'Venda por conta e ordem';
        }
        else{
            $retorno['tipo_venda'] = 'Venda normal';
        }

        $retorno['itens'] = [];

        $devolucaoNotaNasajonObj = $notaVendaNasajonObj->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');

        $devolucaoNotaQuery = DevolucaoNota::with('produtos')
            ->whereDoesntHave('aprovadores', function($query){
                $query->where('devolucao_nota_status_id', 7);
            })
            ->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT))
            ->where('nota_fiscal', $fields['nota_fiscal'])
            ->where('devolucao_nota_status_id', '!=', 11);

        if(isset($fields['id']) && !empty($fields['id'])){
            try {
                $id = Crypt::decrypt($fields['id']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Ocorreu um erro ao processar sua requisição, por favor atualize a tela',
                        'error' => [
                            'nota_fiscal' => 'Ocorreu um erro ao processar sua requisição, por favor atualize a tela'
                        ],
                        'response' => []
                    ], 422
                );
            }

            $devolucaoNotaQuery->where('id', '!=', $id);
        }

        $devolucaoNotaObj = $devolucaoNotaQuery->get();

        if($devolucaoNotaObj->containsStrict('valor_parcial', false)){

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'A nota já tem um processo de devolução completa',
                    'error' => [
                        'nota_fiscal' => 'A nota já tem um processo de devolução completa'
                    ],
                    'response' => []
                ], 422
            );    
        }
        else if($devolucaoNotaObj->contains(function($devolucao) { return in_array($devolucao->devolucao_nota_status_id, [1,2]); })){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Já há uma digitação de requisição de devolução pendente para esta nota, favor verificar',
                    'error' => [
                        'nota_fiscal' => 'Já há uma digitação de requisição de devolução pendente para esta nota, favor verificar'
                    ],
                    'response' => []
                ], 422
            );
        }


        $notaVendaNasajonObj->item->each(function($item) use (&$retorno, $devolucaoNotaNasajonObj, $devolucaoNotaObj){

            $devolvidos = 0;
            
            $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                ->pluck('itens_faturamento')
                ->flatten()
                ->where('Item - Código', $item->produto_detalhes->codigo_produto);

            if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
            }

            $requisicaoDevolucaoItemObj = $devolucaoNotaObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
            
            if($requisicaoDevolucaoItemObj->isNotEmpty()){
                $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
            }
            
            if($item->quantidade - $devolvidos > 0){
                
                $linha = [];
            
                $linha['id'] = $item->id_item_nota;
                $linha['codigo_produto'] = $item->produto_detalhes->codigo_produto;
                $linha['grupo'] = $item->produto_detalhes->grupo;
                $linha['descricao'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $item->produto_detalhes->descricao . "'>" . $item->produto_detalhes->descricao . "</div></div>";
                $linha['quantidade_comprada'] = parserValor($item->quantidade - $devolvidos);
                $linha['devolvidos'] = $devolvidos;

                $retorno['itens'][] = $linha; 

            }
        });

        if(empty($retorno['itens'])){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Todos os itens desta nota já foram devolvidos ou estão em processo de devolução',
                    'error' => [
                        'nota_fiscal' => 'Todos os itens desta nota já foram devolvidos ou estão em processo de devolução',
                    ],
                    'response' => []
                ], 422
            );    
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => $retorno
            ], 220
        );

    }

    public function modalDevolucoes($cnpjs, $codigos){
        $devolucoesObj = DevolucaoNota::with('status_detalhes', 'motivo_devolucao', 'cliente')
            ->whereIn('cliente_cpf_cnpj', $cnpjs)
            ->whereNotIn('devolucao_nota_status_id',  ['8','11'])
            ->get();

        $devolucoes = [];
        $concatDevolucoes = collect([]);
        $devolucoesConcluidasObj = collect([]);

        $devolucoesConcluidasObj = FaturamentoNotaNasajon::with('cliente', 'devolucoes')
            ->select(DB::raw('"Identificador Documento" as id,
                "Id_Nota_Origem",
                "Número Documento" as documento, 
                "Identificador Cliente", 
                "Estabelecimento" as estabelecimento,
                "Valor Documento" as valor,
                "Data Lançamento" as data_movimentacao'))
            ->whereIn('Cliente', $codigos)
            ->whereIn('cfop', ['1201', '1202', '2201', '2202'])
            ->get();

        $hoje = Carbon::Now();
        
        $devolucoesObj->each(function($devolucao) use(&$devolucoes, $hoje){
            $linha = [];

            $linha['id'] = Crypt::encrypt($devolucao->id);
            $linha['devolucao_numero'] = $devolucao->id;
            $linha['nota_id'] = $devolucao->nota_id;
            $linha['nota_devolucao'] = str_pad($devolucao->nota_cliente_numero, 9, '0', STR_PAD_LEFT);
            $linha['nota_numero'] = $devolucao->nota_fiscal;
            $linha['cliente'] = $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj;
            $linha['parcial'] = ($devolucao->valor_parcial)?'Parcial':'Completa';
            $linha['motivo'] = $devolucao->motivo_devolucao->descricao;
            $linha['valor'] = parserValor($devolucao->valor);
            $linha['status'] = $devolucao->status_detalhes->descricao;

            if($devolucao->devolucao_nota_status_id == 7){
                $linha['dias_aberto'] = $devolucao->created_at->diffInDays($devolucao->updated_at);
                $linha['dias_fase'] = '';
            }
            else{
                $linha['dias_aberto'] = $devolucao->created_at->diffInDays($hoje);
                $linha['dias_fase'] = $devolucao->updated_at->diffInDays($hoje);
            }

            if(!is_null($devolucao->nota_cliente_arquivo) && Storage::exists($this->storage . '/nota_cliente/' . $devolucao->nota_cliente_arquivo)){
                $linha['nota_devolucao_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucao->nota_cliente_arquivo);
            }
            else{
                $linha['nota_devolucao_arquivo'] = '';
            }

            $linha['origem'] = 'Portal';

            $linha['entrada'] = parserData($devolucao->created_at);

            $devolucoes[] = $linha;
        });

        $devolucoesConcluidasObj->each(function($devolucao) use(&$devolucoes){
            $linha = [];
            $linha['id'] = '';
            $linha['devolucao_numero'] = '';
            $linha['nota_id'] = $devolucao->id;
            $linha['nota_devolucao'] = $devolucao->documento;
            $linha['nota_devolucao_arquivo'] = '';
            $linha['nota_numero'] = $devolucao->devolucoes->count() > 0? $devolucao->devolucoes[0]["Número Documento"] : '';
            $linha['cliente'] = $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj;
            $linha['parcial'] = '';
            $linha['motivo'] = '';
            $linha['valor'] = parserValor($devolucao->valor);
            $linha['status'] = '';
            $linha['dias_aberto'] = '';
            $linha['dias_fase'] = '';
            $linha['origem'] = 'Nasajon';

            $linha['entrada'] = parserData($devolucao->data_movimentacao);
            
            $devolucoes[] = $linha;
        });

        return $devolucoes;
    }

    public function gerarPdf(Request $request, $id, $tipo = null){

        try {
            $id = Crypt::decrypt($id);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::
            with([
                'nota_nasajon',
                'nota_nasajon.cliente',
                'nota_nasajon.pedido',
                'nota_nasajon.pedido',
                'produtos',
                'produtos.produto_na_nota',
                'produtos.produto_na_nota.produto_detalhes',
                'estabelecimentoDetalhes',             
                'estabelecimentoDetalhes.pessoa',
                'estabelecimentoDetalhes.endereco',
                'estabelecimentoDetalhes.cidadeDetalhes',
                'nota_nasajon.item',
                'nota_nasajon.item.produto_detalhes',
                'nota_nasajon.faturamento',
                'cliente'
            ])
            ->find($id);

        $dados = [
            'itens' => [],
            'destinatario' => [],
            'emitente' => [],
            'natureza_operacao' => '',
            'total_produtos' => parserValor($devolucaoNotaObj->valor)
        ];

        $pedidoRemessaObj = PedidosVendaNasajon::with('nota', 'nota.itens_nota')->where('numero', $devolucaoNotaObj->nota_nasajon->pedido->numero)
            ->where('grupodeoperacao', 'REMESSA')
            ->where('cliente_codigo', $devolucaoNotaObj->cliente->codigo)
            ->where('estabelecimento_codigo', $devolucaoNotaObj->nota_nasajon->pedido->estabelecimento_codigo)
            ->first();

        if(isset($tipo) && $tipo == 'armazem'){

            $estabelecimentoObj = NasajonEstabelecimento::
                with('pessoa', 'endereco', 'cidadeDetalhes')
                ->where('codigo' , 20)
                ->first(); 

            if($estabelecimentoObj->cidadeDetalhes->uf == $devolucaoNotaObj->nota_nasajon->cliente->uf){
                $dados['natureza_operacao'] = '5.949 Outra saída de mercadoria ou prestação de serviço não especificado';
                $dados['cfop'] = '5949';
            }
            else{
                $dados['natureza_operacao'] = '6.949 Outra saída de mercadoria ou prestação de serviço não especificado';
                $dados['cfop'] = '6949';
            }

            $dados['nota'] = [
                'tipo' => ($devolucaoNotaObj->valor_parcial ? 'parcial': 'total'),
                'numero' => $pedidoRemessaObj->nota->numero,
                'emissao' => parserData($pedidoRemessaObj->nota->emissao),
                'baseicms' => 0,
                'basesubst' => parserValor($pedidoRemessaObj->nota->basesubst),
                'valoricms' => 0,
                'seguro' => parserValor($pedidoRemessaObj->nota->seguro),
                'outras' => parserValor($pedidoRemessaObj->nota->outras),
                'frete' => parserValor($pedidoRemessaObj->nota->frete),
                'valoricmsst' => parserValor($pedidoRemessaObj->nota->valoricmsst),
                'total_produto' => 0,
                'total_desconto' => parserValor($pedidoRemessaObj->nota->total_desconto),
                'valor' => 0,
                'ipi' => 0
            ];

            $dados['destinatario'] = [
                'nome' => $estabelecimentoObj->pessoa->nome,
                'cpf_cnpj' => $estabelecimentoObj->pessoa->cnpj,
                'endereco' => $estabelecimentoObj->tipo_logradouro . ' ' . $estabelecimentoObj->logradouro . ' ' .$estabelecimentoObj->numero,
                'bairro' => $estabelecimentoObj->bairro,
                'cep' => $estabelecimentoObj->cep,
                'municipio' => $estabelecimentoObj->cidade,
                'telefone' => '(' . $estabelecimentoObj->dddtel. ') ' . $estabelecimentoObj->telefone,
                'uf' => $estabelecimentoObj->cidadeDetalhes->uf,
                'ie' => $estabelecimentoObj->inscricaoestadual,
            ];
        }
        else{

            if($devolucaoNotaObj->estabelecimentoDetalhes->cidadeDetalhes->uf == $devolucaoNotaObj->nota_nasajon->cliente->uf){
                $dados['natureza_operacao'] = '5.202 Devolução de compra para comercialização';
                $dados['cfop'] = '5202';
            }
            else{
                $dados['natureza_operacao'] = '6.202 Devolução de compra para comercialização';
                $dados['cfop'] = '6202';
            }

            if(in_array($devolucaoNotaObj->estabelecimento, ['03', '04'])){
                $dados['nota'] = [
                    'tipo' => ($devolucaoNotaObj->valor_parcial ? 'parcial': 'total'),
                    'numero' => $devolucaoNotaObj->nota_fiscal,
                    'emissao' => parserData($pedidoRemessaObj->nota->emissao),
                    'baseicms' => 0,
                    'basesubst' => parserValor($pedidoRemessaObj->nota->basesubst),
                    'valoricms' => 0,
                    'seguro' => parserValor($pedidoRemessaObj->nota->seguro),
                    'outras' => parserValor($pedidoRemessaObj->nota->outras),
                    'frete' => parserValor($pedidoRemessaObj->nota->frete),
                    'valoricmsst' => parserValor($pedidoRemessaObj->nota->valoricmsst),
                    'total_produto' => 0,
                    'total_desconto' => parserValor($pedidoRemessaObj->nota->total_desconto),
                    'valor' => 0,
                    'ipi' => 0
                ];
            }
            else{
                $dados['nota'] = [
                    'tipo' => ($devolucaoNotaObj->valor_parcial ? 'parcial': 'total'),
                    'numero' => $devolucaoNotaObj->nota_fiscal,
                    'emissao' => parserData($devolucaoNotaObj->data_emissao),
                    'baseicms' => 0,
                    'basesubst' => parserValor($devolucaoNotaObj->nota_nasajon->basesubst),
                    'valoricms' => 0,
                    'seguro' => parserValor($devolucaoNotaObj->nota_nasajon->seguro),
                    'outras' => parserValor($devolucaoNotaObj->nota_nasajon->outras),
                    'frete' => parserValor($devolucaoNotaObj->nota_nasajon->frete),
                    'valoricmsst' => parserValor($devolucaoNotaObj->nota_nasajon->valoricmsst),
                    'total_produto' => 0,
                    'total_desconto' => parserValor($devolucaoNotaObj->nota_nasajon->total_desconto),
                    'valor' => 0,
                    'ipi' => 0
                ];
            }

            if($devolucaoNotaObj->valor_parcial){
                $dados['nota']['valoricms'] = 0;
                $dados['nota']['valor'] = 0;
                $dados['nota']['total_produto'] = 0;
                $dados['nota']['baseicms'] = 0;
            }

            $dados['destinatario'] = [
                'nome' => $devolucaoNotaObj->estabelecimentoDetalhes->pessoa->nome,
                'cpf_cnpj' => $devolucaoNotaObj->estabelecimentoDetalhes->pessoa->cnpj,
                'endereco' => $devolucaoNotaObj->estabelecimentoDetalhes->tipo_logradouro . ' ' . $devolucaoNotaObj->estabelecimentoDetalhes->logradouro . ' ' .$devolucaoNotaObj->estabelecimentoDetalhes->numero,
                'bairro' => $devolucaoNotaObj->estabelecimentoDetalhes->bairro,
                'cep' => $devolucaoNotaObj->estabelecimentoDetalhes->cep,
                'municipio' => $devolucaoNotaObj->estabelecimentoDetalhes->cidade,
                'telefone' => '(' . $devolucaoNotaObj->estabelecimentoDetalhes->dddtel. ') ' . $devolucaoNotaObj->estabelecimentoDetalhes->telefone,
                'uf' => $devolucaoNotaObj->estabelecimentoDetalhes->cidadeDetalhes->uf??'',
                'ie' => $devolucaoNotaObj->estabelecimentoDetalhes->inscricaoestadual,
            ];
        }

        if($devolucaoNotaObj->valor_parcial){

            $devolucaoNotaObj->produtos->each(function($produto) use (&$dados, $tipo, $devolucaoNotaObj, $pedidoRemessaObj){

                $cst_origem = $produto->produto_na_nota->origem_mercadoria . $produto->produto_na_nota->icms_cst;

                if(isset($tipo) && $tipo == 'armazem'){

                    if($cst_origem == '000'){
                        $cst = '041';
                    }
                    else if($cst_origem == '100'){
                        $cst = '141';
                    }
                    else if($cst_origem == '200'){
                        $cst = '241';
                    }
                    else if($cst_origem == '400'){
                        $cst = '441';
                    }
                    else{
                        $cst = $cst_origem;
                    }
                }
                else{
                    if($cst_origem == '041'){
                        $cst = '000';
                    }
                    else if($cst_origem =='141') {
                        $cst = '100';
                    }
                    else if($cst_origem == '241'){
                        $cst = '200';
                    }
                    else if($cst_origem == '441'){
                        $cst = '400';
                    }
                    else{
                        $cst = $cst_origem;
                    }
                }

                if(in_array($devolucaoNotaObj->estabelecimento, ['03', '04'])){
                    if(isset($tipo) && $tipo == 'armazem'){
                        $produto_remessa = $pedidoRemessaObj->nota->itens_nota->firstWhere('codigo', $produto->produto_na_nota->cod_produto);
                        $calculo_icms = ($produto->produto_na_nota->aliquota_icms / 100 ) * ($produto->produto_na_nota->valor_unitario * $produto->quantidade);
                        $icms = parserValor($calculo_icms);
                        $ipi = parserValor($produto_remessa->valoripi);
                        $porcentagem_ipi = parserValor($produto_remessa->valoraliquotaipi);
                        $porcentagem_icms = parserValor($produto->produto_na_nota->aliquota_icms);
                        

                        $dados['nota']['ipi'] += $produto->produto_na_nota->valor_ipi;
                    }
                    else{
                        $produto_remessa = $pedidoRemessaObj->nota->itens_nota->firstWhere('codigo', $produto->produto_na_nota->cod_produto);
                        $calculo_icms = ($produto_remessa->valoraliquotaicms / 100 ) * ($produto_remessa->valorunitariocomercial * $produto->quantidade);
                        $icms = parserValor($calculo_icms);
                        $porcentagem_icms = parserValor($produto_remessa->valoraliquotaicms);
                        $ipi = parserValor($produto->produto_na_nota->valor_ipi);
                        $porcentagem_ipi = parserValor($produto->produto_na_nota->aliquota_ipi);

                        $dados['nota']['ipi'] += $produto_remessa->valoripi;
                        $dados['nota']['valoricms'] += $calculo_icms;

                    }
                }
                else{
                    $calculo_icms = ($produto->produto_na_nota->aliquota_icms / 100 ) * ($produto->produto_na_nota->valor_unitario * $produto->quantidade);
                    $icms = parserValor($calculo_icms);
                    $ipi = parserValor($produto->produto_na_nota->valor_ipi);
                    $porcentagem_icms = parserValor($produto->produto_na_nota->aliquota_icms);
                    $porcentagem_ipi = parserValor($produto->produto_na_nota->aliquota_ipi);

                    $dados['nota']['ipi'] += $produto->produto_na_nota->valor_ipi;
                    $dados['nota']['valoricms'] += ($calculo_icms > 0) ? $calculo_icms : 0;
                    
                }

                $dados['nota']['valor'] += $produto->produto_na_nota->valor_unitario * $produto->quantidade;
                $dados['nota']['total_produto'] += $produto->produto_na_nota->valor_unitario * $produto->quantidade;

                $linha = [
                    'codigo' => $produto->produto_na_nota->cod_produto,
                    'descricao' => $produto->produto_na_nota->produto_detalhes->descricao,
                    'ncm' => $produto->produto_na_nota->ncm,
                    'cst' => $cst,
                    'cfop' => $produto->produto_na_nota->cfop,
                    'unidade' => $produto->produto_na_nota->unidade,
                    'quantidade' => parserValor($produto->quantidade),
                    'valor' => parserValor($produto->produto_na_nota->valor_unitario),
                    'desconto' => parserValor($produto->produto_na_nota->desc_produto),
                    'total' => parserValor($produto->produto_na_nota->valor_unitario * $produto->quantidade),
                    'base_calculo' => parserValor($produto->produto_na_nota->valor_unitario * $produto->quantidade),
                    'icms' => $icms,
                    'ipi' => $ipi,
                    'porcentagem_icms' => $porcentagem_icms,
                    'porcentagem_ipi' => $porcentagem_ipi
                ];

                if(isset($tipo) && $tipo == 'armazem'){
                    $linha['base_calculo'] = '0,00';
                }
                else{
                    $dados['nota']['baseicms'] += $produto->produto_na_nota->valor_unitario * $produto->quantidade;
                }

                $dados['itens'][] = $linha;
            });

        }
        else{
            $devolucaoNotaObj->nota_nasajon->item->each(function($produto) use (&$dados, $tipo, $devolucaoNotaObj, $pedidoRemessaObj){

                $cst_origem = $produto->origem_mercadoria . $produto->icms_cst;

                if(isset($tipo) && $tipo == 'armazem'){

                    if($cst_origem == '000'){
                        $cst = '041';
                    }
                    else if($cst_origem == '100'){
                        $cst = '141';
                    }
                    else if($cst_origem == '200'){
                        $cst = '241';
                    }
                    else if($cst_origem == '400'){
                        $cst = '441';
                    }
                    else{
                        $cst = $cst_origem;
                    }
                }
                else{
                    if($cst_origem == '041'){
                        $cst = '000';
                    }
                    else if($cst_origem =='141') {
                        $cst = '100';
                    }
                    else if($cst_origem == '241'){
                        $cst = '200';
                    }
                    else if($cst_origem == '441'){
                        $cst = '400';
                    }
                    else{
                        $cst = $cst_origem;
                    }
                }

                if(in_array($devolucaoNotaObj->estabelecimento, ['03', '04'])){
                    if(isset($tipo) && $tipo == 'armazem'){
                        $icms = '0,00';
                        $ipi = parserValor($produto->valor_ipi);
                        $porcentagem_icms = parserValor($produto->aliquota_icms);
                        $porcentagem_ipi = parserValor($produto->aliquota_ipi);

                        $dados['nota']['ipi'] += $produto->produto;
                    }
                    else{
                        $produto_remessa = $pedidoRemessaObj->nota->itens_nota->firstWhere('codigo', $produto->cod_produto);

                        $icms = parserValor($produto_remessa->valoricms);
                        $ipi = parserValor($produto_remessa->valoripi);
                        $porcentagem_icms = parserValor($produto_remessa->valoraliquotaicms);
                        $porcentagem_ipi = parserValor($produto_remessa->valoraliquotaipi);

                        $dados['nota']['ipi'] += $produto_remessa->valoripi;
                        $dados['nota']['valoricms'] += $produto_remessa->valoricms;
                    }
                }
                else{
                    $icms = parserValor($produto->valor_icms);
                    $ipi = parserValor($produto->valor_ipi);
                    $porcentagem_icms = parserValor($produto->aliquota_icms);
                    $porcentagem_ipi = parserValor($produto->aliquota_ipi);

                    $dados['nota']['ipi'] += $produto->valor_ipi;
                    $dados['nota']['valoricms'] += $produto->valor_icms;

                }

                $dados['nota']['valor'] += $produto->valor_total;
                $dados['nota']['total_produto'] += $produto->valor_total;

                $linha = [
                    'codigo' => $produto->cod_produto,
                    'descricao' => $produto->produto_detalhes->descricao,
                    'ncm' => $produto->ncm,
                    'cst' => $cst,
                    'cfop' => $produto->cfop,
                    'unidade' => $produto->unidade,
                    'quantidade' => parserValor($produto->quantidade),
                    'valor' => parserValor($produto->valor_unitario),
                    'desconto' => parserValor($produto->desc_produto),
                    'total' => parserValor($produto->valor_total),
                    'base_calculo' => parserValor($produto->valor_total),
                    'icms' => $icms,
                    'ipi' => $ipi,
                    'porcentagem_icms' => $porcentagem_icms,
                    'porcentagem_ipi' => $porcentagem_ipi,
                ];

                if(isset($tipo) && $tipo == 'armazem'){
                    $linha['base_calculo'] = '0,00';
                }
                else{
                    $dados['nota']['baseicms'] += $produto->valor_total;
                }

                $dados['itens'][] = $linha;
            });
        }

        $dados['nota']['valor'] = parserValor($dados['nota']['valor']);
        $dados['nota']['total_produto'] = parserValor($dados['nota']['total_produto']);
        $dados['nota']['baseicms'] = parserValor($dados['nota']['baseicms']);
        $dados['nota']['valoricms'] = parserValor($dados['nota']['valoricms']);

        $dados['nota']['ipi'] = parserValor($dados['nota']['ipi']);

        $dados['emitente'] = [
            'nome' => $devolucaoNotaObj->nota_nasajon->cliente->nome,
            'fantasia' => $devolucaoNotaObj->nota_nasajon->cliente->nomefantasia,
            'endereco' => $devolucaoNotaObj->nota_nasajon->cliente->tipologradouro . ' ' . $devolucaoNotaObj->nota_nasajon->cliente->logradouro,
            'numero' => $devolucaoNotaObj->nota_nasajon->cliente->numero,
            'complemento' => $devolucaoNotaObj->nota_nasajon->cliente->complemento,
            'bairro' => $devolucaoNotaObj->nota_nasajon->cliente->bairro,
            'municipio' => $devolucaoNotaObj->nota_nasajon->cliente->cidade,
            'uf' => $devolucaoNotaObj->nota_nasajon->cliente->uf,
            'cep' => $devolucaoNotaObj->nota_nasajon->cliente->cep,
            'telefone' => $devolucaoNotaObj->nota_nasajon->cliente->telefones,
            'ie' => $devolucaoNotaObj->nota_nasajon->cliente->inscricaoestadual,
            'cpf_cnpj' => $devolucaoNotaObj->nota_nasajon->cliente->cpf_cnpj
        ];

        return view('pdf.danfe')->with($dados);
    }

	public function downloadXml(Request $request){

		$fields = $request->only('id', 'numero');

        $notaImportadaEntrada = NotasImportadasEntrada::find($fields['id']);

        $xml = $notaImportadaEntrada->xml;

		return response()->make($xml, 200, [
			'Pragma' => 'public',
			'Expires' => '0',
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Content-Type' => 'text/xml',
			'Content-Disposition' => 'attachment; filename="xml_' . $fields['numero'] . '.xml"',
			'Content-Transfer-Encoding' => 'binary',
		]);
	}


    public function modalDevolucoesFornecedor($cnpj){
       
        ini_set('memory_limit', '1024M');
 
        $estabelecimentos = returnEmpresasNasajonView();
		$notasNasasjonObj = NotasNasajon::query()->with(['ocorrenciaEntrega', 'revisao_vendedor_comissao', 'confirmacaoNotaSaida']);
		$tipo_operacao_nasajon = $this->getOperacaoNasajon('devolucao');
        $notasNasasjonObj->where('cliente_documento',  $cnpj);
        $notasNasasjonObj->whereIn('operacao_codigo', $tipo_operacao_nasajon);
        $notasNasasjonObj = $notasNasasjonObj->get();

		$devolucoes = [];
   
		$notasNasasjonObj->each(function($nota_nasajon) use (&$devolucoes, $estabelecimentos){
            
			$query = $nota_nasajon->confirmacaoNotaSaida;
			if(!empty($query)){
				$data_saida = parserData($query->data_saida);

			}else{
				$data_saida = '';
			}

			if(isset($nota_nasajon->ocorrenciaEntrega->nota_id)){
				$nota_id_ocorrencia = true;
			}else{
				$nota_id_ocorrencia = false;
			}

			$devolucoes[] = [
				'id_nota' => $nota_nasajon->id,
				'nota_id_ocorrencia' =>  $nota_id_ocorrencia,
				'numnfe' => $nota_nasajon->numero,
				'dtemis' => parserData($nota_nasajon->emissao),
                'dtsaida' => parserData($data_saida),
                'valtotdoc' => parserValor($nota_nasajon->valor),
                'estabelecimento' => ($nota_nasajon->estabelecimento_descricao),
				'estabelecimento_nome' => '<div><div data-toggle="tooltip" data-html="true" title="' . ($estabelecimentos[(int) $nota_nasajon->estabelecimento_codigo]) . '">' . ($estabelecimentos[(int) $nota_nasajon->estabelecimento_codigo]) . '</div></div>',
				'origem' => 'nasajon',
				'tipo_operacao' => '<div><div data-toggle="tooltip" data-html="true" title="' . $nota_nasajon->naturezaoperacao . '">' .$nota_nasajon->naturezaoperacao . '</div></div>',
				'dt_doc' => parserData($nota_nasajon->emissao),
				'cliente' => $nota_nasajon->cliente_nome." - ".$nota_nasajon->cliente_documento,
			];

        });
    
        return $devolucoes;
    }

    private function getOperacaoNasajon($tipo_operacao){
		$operacao = [];
		$venda = [
			'PEDIDOISENTO',
			'PEDVENDATORO',
			'VENDA',
			'VENDAAORDEM',
			'VENDAISENTO',
			'VENDALOJAS',
			'VENDAORDEMTORO',
			'VENDAORGPUBLICO',
			'VENDATORO'
		];
		$remessa = [
			'REMESSAORDEM',
			'REMESSAORDEMTORO',
			'REMESSAORDEMTOROTERC',
			'REMARMAZMOVTO',
			'REMESSAORDEMSEMMOV',
		];
		$devolucao = [
			'DEVOLUCAODECOMPRA'
		];
		$transferencia = [
			'TRANSFERENCIAESTABELECIMENTOS',
			'TRANSFESTABSEMICMS',
		];
		switch($tipo_operacao){
			case 'venda':
				$operacao = $venda;
				break;
			case 'remessa':
				$operacao = $remessa;
				break;
			case 'devolucao':
				$operacao = $devolucao;
				break;
			case 'transferencia':
				$operacao = $transferencia;
				break;
			default:
				$operacao = array_merge($venda, array_merge($remessa, array_merge($devolucao, $transferencia)));
			break;
		}
		return $operacao;
	}

    public function removerDocumentoDiversos(Request $request){
        $id_crypt = $request->only(['id_documento']);

        try {
            $id = Crypt::decrypt($id_crypt['id_documento']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $documento = DevolucaoNotasDocumento::find($id);
        $documento->deleted_by = Auth::user()->id;
        $documento->save();
        Storage::delete($this->storage_files . $documento->caminho);
        $documento->delete();

        $response = [
            "status" => 'success',
            "message" => 'Excluido com sucesso',
            "error" => [],
            "response" => ['ok']
        ];

        return response()->json($response, 200);
    }

    public function removerDocumentoIsento(Request $request){
        $id_crypt = $request->only(['id_isento']);

        try {
            $id = Crypt::decrypt($id_crypt['id_isento']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $documento = DevolucaoNotasDocumento::find($id);
        $documento->deleted_by = Auth::user()->id;
        $documento->save();
        Storage::delete($this->storage_files . $documento->caminho);
        $documento->delete();

        $response = [
            "status" => 'success',
            "message" => 'Excluido com sucesso',
            "error" => [],
            "response" => ['ok']
        ];

        return response()->json($response, 200);
    }

    public function reenviarEmailTransportadora(Request $request){
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [$e],
                    'response' => []
                ], 422
            );
        }

        try {
            $documento = DevolucaoNotasDocumento::with(['devolucaoNotas.nota_nasajon.transportadora','devolucaoNotas.nota_remessa_nasajon.transportadora','devolucaoNotas'])->find($id);
            $nome_transportadora = '';
            $email = '';
            
            if((!empty($documento->devolucaoNotas->nota_nasajon->transportadora->nomefantasia))){
                $nome_transportadora = $documento->devolucaoNotas->nota_nasajon->transportadora->nomefantasia;
                $email = $documento->devolucaoNotas->transportador_email;
            }else if(!empty($documento->devolucaoNotas->nota_remessa_nasajon->transportadora->nomefantasia)){
                $nome_transportadora = $documento->nota_remessa_nasajon->transportadora->nomefantasia;
                $email = $documento->devolucaoNotas->transportador_email;
            }

            if(empty($email)){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'E-mail não encontrado!',
                        'error' => [],
                        'response' => []
                    ], 422
                );
            }

            $mensagem = 'Prezado '.$nome_transportadora.',<br><br>';
            $mensagem .= 'Segue documento em anexo '.$documento->nome_arquivo.', referente a devolução da nota fiscal '.$documento->devolucaoNotas->nota_fiscal.'.';
            $emailControllerObj = new EmailController;
            $returnEmail = $emailControllerObj->sendEmailToken('00', "documentos_devolucao_transportadora", $email, ['corpo' => $mensagem], [$this->storage_files. $documento->caminho => ['as' => 'documento_devolucao']], []);
            
            $documento->updated_by = Auth::id();
            $documento->save();

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'E-mail Enviado!',
                    'error' => [],
                    'response' => []
                ], 200
            );
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [$e],
                    'response' => []
                ], 422
            );
        }
    }
    
}
