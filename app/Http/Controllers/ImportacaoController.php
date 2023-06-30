<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\ComprasNasajon;
use App\FornecedorNasajon;
use App\Importacao;
use App\ProdutoEspecificacao;
use App\ImportacaoDocumento;

use App\ImportacaoItem;
use App\ImportacaoEmbarque;
use App\ImportacaoCusto;
use App\ImportacaoFinanceiro;
use App\ImportacaoFinanceiroLancamento;
use App\ImportacaoCustoOutraDespesa;
use App\ImportacaoValorPadrao;
use App\ImportacaoFollowUp;
use App\ImportacaoFollowUpHistoricoAprovacao;
use App\Cotacoes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ImportacaoRequest;
use App\Http\Requests\ImportacaoAdicionarMudancaValorRequest;

use App\Http\Controllers\ImportacaoDadoComplemetarFollowUpController;
use App\ProdutoGrupo;

class ImportacaoController extends Controller
{
    public $path = 'public/importacao/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Importacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Importacao');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.importacao.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function indexPedido(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoPedidoAberto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoPedidoAberto');

        $situacao_pedido = $this->situacaoPedido();

        return view('programs.importacao_consulta_pedido_aberto.index')->with(['situacao_pedidos' => $situacao_pedido]);
    }
    
    public function indexCompraProduto(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoCompraProduto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoCompraProduto');
    

 
        return view('programs.importacao_consulta_compra_produto.index');
    }

    public function modalAdicionar() {
        $importacaoDadoComplemetarFollowUpControllerObj = new ImportacaoDadoComplemetarFollowUpController;

        $dados = [
            'tipos_cores' => $importacaoDadoComplemetarFollowUpControllerObj->dadosTipoCores(),
            'tipo_aprovacoes_simples' => $importacaoDadoComplemetarFollowUpControllerObj->dadosSimplesAprovacao(),
            'tipo_aprovacoes_parcial' => $importacaoDadoComplemetarFollowUpControllerObj->dadosAprovacaoParcial(),
        ];

        return view('programs.importacao.modal.adicionar')->with(['dados' => $dados]);
    }

    public function adicionar(ImportacaoRequest $request){
        ini_set('memory_limit','80M');
        ini_set('post_max_size', '50M');
        $fields = $request->only('id', 'fornecedor', 'numero_proforma', 'pedido_compras', 'referencia', 'data_proforma', 'previsao_carta_programa', 'respresentante', 'carga_pronta_previsao', 'carga_pronta_realizado', 'embarque_previsao', 'embarque_realizado', 'chegada_porto_previsao', 'chegada_porto_realizado', 'data_di_previsao', 'data_di_realizado', 'devolucao_cntr_previsao', 'devolucao_cntr_realizado', 'quality_sample_enviado', 'quality_sample_recebido', 'handlooms_enviado', 'handlooms_recebido', 'strike_off_enviado', 'strike_off_recebido', 'amostra_embarque_enviado', 'amostra_embarque_recebido', 'aprovacao_amostra_embarque', 'aprovado_embarque_produto_codigo', 'aprovado_embarque_produto', 'tempo_producao', 'valor_contabil_unitario', 'porto_origem','porto_destino','agente_compra','armador','etd_booking','eta_booking','numero_bl','transit_time_chegada','transit_time_saida','arquivo_carta_programada', 'arquivo_proforma','arquivo_conciliator_invoice','arquivo_packing_list','arquivo_bl','arquivo_contrato_cambio','arquivo_nf_importacao','arquivo_exoneracao','arquivo_nf_remessa','arquivo_di','arquivo_ci','ii','ipi','pis','cofins','afrmm','taxa_siscomex','sda','honorarios','expediente','valor_li','agencia_maritima','armazem','laudo','outras_despesas','icms_saida','seguro', 'debito_credito_financeiro', 'data_embarque_financeiro', 'arquivo_fechamento_processo', 'transporte_rodoviario', 'arquivo_armazenagem', 'arquivo_afrmm', "envio_das_cores","envio_das_cores_previsao","envio_das_cores_enviado","envio_das_cores_recebido","aprovacao_quality_sample","quality_sample_previsao","quality_sample_enviado","quality_sample_recebido","aprovacao_quality_sample_previsao","aprovacao_quality_sample_realizado","aprovacao_laboratorio","laboratorio_previsao","laboratorio_enviado","laboratorio_recebido","aprovacao_laboratorio_realizado","tempo_producao_previsao","tempo_producao","aprovacao_amostra_embarque","amostra_embarque_previsao","amostra_embarque_enviado","amostra_embarque_recebido","aprovacao_amostra_embarque_previsao","aprovacao_amostra_embarque_enviado","autorizacao_embarque_previsao","autorizacao_embarque_enviado","envio_das_cores_revisao","quality_sample_transportadora", "quality_sample_awb","amostra_embarque_transportadora","amostra_embarque_awb");

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor = $fornecedor->first();

        $proforma = ComprasNasajon::select();
        $proforma->where('proforma', 'ILIKE', ($fields['numero_proforma']));
        $proforma->where('estabelecimento', '03');
        $proforma = $proforma->first();

        $respresentante = FornecedorNasajon::select();
        $respresentante->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['respresentante']));
        $respresentante = $respresentante->first();

        $importacaoObj = Importacao::find($id);
        $importacaoObj->estabelecimento_codigo = $proforma->estabelecimento;
        $importacaoObj->fornecedor_codigo = $fornecedor->codigo;
        $importacaoObj->numero_proforma = $proforma->proforma;
        $importacaoObj->pedido_compras = $proforma->numero_pedido;
        $importacaoObj->referencia = $fields['referencia'];
        $importacaoObj->data_proforma = $proforma->data_compra;
        if(!empty($fields['previsao_carta_programa'])){
            $data_previsao_carta_programa = Carbon::createFromFormat('d/m/Y', $fields['previsao_carta_programa'])->setTime(0,0,0);
            $importacaoObj->data_previsao_carta_programa = $data_previsao_carta_programa;
        }
        $importacaoObj->data_previsao_recebimento = $proforma->previsao_entrega;
        if(!empty($fields['respresentante'])){
            $respresentante = FornecedorNasajon::select();
            $respresentante->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['respresentante']));
            $respresentante = $respresentante->first();

            $importacaoObj->respresentante_codigo = $respresentante->codigo;
        }
        if(!empty($fields['carga_pronta_previsao'])){
            $data_carga_pronta_previsao = Carbon::createFromFormat('d/m/Y', $fields['carga_pronta_previsao'])->setTime(0,0,0);
            $importacaoObj->data_carga_pronta_previsao = $data_carga_pronta_previsao;
        }else{
            $importacaoObj->data_carga_pronta_previsao = null;
        }
        if(!empty($fields['carga_pronta_realizado'])){
            $data_carga_pronta_realizado = Carbon::createFromFormat('d/m/Y', $fields['carga_pronta_realizado'])->setTime(0,0,0);
            $importacaoObj->data_carga_pronta_realizado = $data_carga_pronta_realizado;
        }else{
            $importacaoObj->data_carga_pronta_realizado = null;
        }
        if(!empty($fields['embarque_previsao'])){
            $data_embarque_previsao = Carbon::createFromFormat('d/m/Y', $fields['embarque_previsao'])->setTime(0,0,0);
            $importacaoObj->data_embarque_previsao = $data_embarque_previsao;
        }else{
            $importacaoObj->data_embarque_previsao = null;
        }
        if(!empty($fields['embarque_realizado'])){
            $data_embarque_realizado = Carbon::createFromFormat('d/m/Y', $fields['embarque_realizado'])->setTime(0,0,0);
            $importacaoObj->data_embarque_realizado = $data_embarque_realizado;
        }else{
            $importacaoObj->data_embarque_realizado = null;
        }
        if(!empty($fields['chegada_porto_previsao'])){
            $data_chegada_porto_previsao = Carbon::createFromFormat('d/m/Y', $fields['chegada_porto_previsao'])->setTime(0,0,0);
            $importacaoObj->data_chegada_porto_previsao = $data_chegada_porto_previsao;
        }else{
            $importacaoObj->data_chegada_porto_previsao = null;
        }
        if(!empty($fields['chegada_porto_realizado'])){
            $data_chegada_porto_realizado = Carbon::createFromFormat('d/m/Y', $fields['chegada_porto_realizado'])->setTime(0,0,0);
            $importacaoObj->data_chegada_porto_realizado = $data_chegada_porto_realizado;
        }else{
            $importacaoObj->data_chegada_porto_realizado = null;
        }
        if(!empty($fields['data_di_previsao'])){
            $data_di_previsao = Carbon::createFromFormat('d/m/Y', $fields['data_di_previsao'])->setTime(0,0,0);
            $importacaoObj->data_di_previsao = $data_di_previsao;
        }else{
            $importacaoObj->data_di_previsao = null;
        }
        if(!empty($fields['data_di_realizado'])){
            $data_di_realizado = Carbon::createFromFormat('d/m/Y', $fields['data_di_realizado'])->setTime(0,0,0);
            $importacaoObj->data_di_realizado = $data_di_realizado;
        }else{
            $importacaoObj->data_di_realizado = null;
        }
        if(!empty($fields['devolucao_cntr_previsao'])){
            $data_devolucao_cntr_previsao = Carbon::createFromFormat('d/m/Y', $fields['devolucao_cntr_previsao'])->setTime(0,0,0);
            $importacaoObj->data_devolucao_cntr_previsao = $data_devolucao_cntr_previsao;
        }else{
            $importacaoObj->data_devolucao_cntr_previsao = null;
        }
        if(!empty($fields['devolucao_cntr_realizado'])){
            $data_devolucao_cntr_realizado = Carbon::createFromFormat('d/m/Y', $fields['devolucao_cntr_realizado'])->setTime(0,0,0);
            $importacaoObj->data_devolucao_cntr_realizado = $data_devolucao_cntr_realizado;
        }else{
            $importacaoObj->data_devolucao_cntr_realizado = null;
        }
        if(!empty($fields['quality_sample_enviado'])){
            $data_quality_sample_enviado = Carbon::createFromFormat('d/m/Y', $fields['quality_sample_enviado'])->setTime(0,0,0);
            $importacaoObj->data_quality_sample_enviado = $data_quality_sample_enviado;
        }else{
            $importacaoObj->data_quality_sample_enviado = null;
        }
        if(!empty($fields['quality_sample_recebido'])){
            $data_quality_sample_recebido = Carbon::createFromFormat('d/m/Y', $fields['quality_sample_recebido'])->setTime(0,0,0);
            $importacaoObj->data_quality_sample_recebido = $data_quality_sample_recebido;
        }else{
            $importacaoObj->data_quality_sample_recebido = null;
        }
        if(!empty($fields['handlooms_enviado'])){
            $data_handlooms_enviado = Carbon::createFromFormat('d/m/Y', $fields['handlooms_enviado'])->setTime(0,0,0);
            $importacaoObj->data_handlooms_enviado = $data_handlooms_enviado;
        }else{
            $importacaoObj->data_handlooms_enviado = null;
        }
        if(!empty($fields['handlooms_recebido'])){
            $data_handlooms_recebido = Carbon::createFromFormat('d/m/Y', $fields['handlooms_recebido'])->setTime(0,0,0);
            $importacaoObj->data_handlooms_recebido = $data_handlooms_recebido;
        }else{
            $importacaoObj->data_handlooms_recebido = null;
        }
        if(!empty($fields['strike_off_enviado'])){
            $data_strike_off_enviado = Carbon::createFromFormat('d/m/Y', $fields['strike_off_enviado'])->setTime(0,0,0);
            $importacaoObj->data_strike_off_enviado = $data_strike_off_enviado;
        }else{
            $importacaoObj->data_strike_off_enviado = null;
        }
        if(!empty($fields['strike_off_recebido'])){
            $data_strike_off_recebido = Carbon::createFromFormat('d/m/Y', $fields['strike_off_recebido'])->setTime(0,0,0);
            $importacaoObj->data_strike_off_recebido = $data_strike_off_recebido;
        }else{
            $importacaoObj->data_strike_off_recebido = null;
        }
        if(!empty($fields['amostra_embarque_enviado'])){
            $data_amostra_embarque_enviado = Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_enviado'])->setTime(0,0,0);
            $importacaoObj->data_amostra_embarque_enviado = $data_amostra_embarque_enviado;
        }else{
            $importacaoObj->data_amostra_embarque_enviado = null;
        }
        if(!empty($fields['amostra_embarque_recebido'])){
            $data_amostra_embarque_recebido = Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_recebido'])->setTime(0,0,0);
            $importacaoObj->data_amostra_embarque_recebido = $data_amostra_embarque_recebido;
        }else{
            $importacaoObj->data_amostra_embarque_recebido = null;
        }
        $importacaoObj->aprovacao_amostra_embarque = $fields['aprovacao_amostra_embarque'] == 0? true : false;
        if(!empty($fields['aprovado_embarque_produto_codigo'])){
            $produto = ProdutoEspecificacao::select();
            $produto->where('codigo_produto', 'ILIKE', ($fields['aprovado_embarque_produto_codigo']));
            $produto = $produto->first();

            $importacaoObj->aprovado_embarque_produto_codigo = $produto->codigo_produto;
        }else{
            $importacaoObj->aprovado_embarque_produto_codigo = null;
        }
        if(!empty($fields['tempo_producao'])){
            $importacaoObj->tempo_producao = intval($fields['tempo_producao']);
        }else{
            $importacaoObj->tempo_producao = null;
        }
        $importacaoObj->updated_by = Auth::id();
        $importacaoObj->save();

        $importacaoEmbarqueObj = ImportacaoEmbarque::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoEmbarqueObj)){
            $importacaoEmbarqueObj = new ImportacaoEmbarque;
            $importacaoEmbarqueObj->importacaos_id = $importacaoObj->id;
        }
        
        $importacaoEmbarqueObj->porto_origem = $fields['porto_origem'];
        $importacaoEmbarqueObj->porto_destino = $fields['porto_destino'];
        $importacaoEmbarqueObj->agente_compra =empty($fields['agesim nte_compra']) ?'':  $fields['agesim nte_compra'];
        $importacaoEmbarqueObj->armador = $fields['armador'];
        $importacaoEmbarqueObj->etd_booking = !isset($fields['etd_booking'])? false : true;
        $importacaoEmbarqueObj->eta_booking = !isset($fields['eta_booking'])? false : true;
        $importacaoEmbarqueObj->numero_bl = $fields['numero_bl'];
        if(!empty($fields['transit_time_chegada'])){
            $transit_time_chegada = Carbon::createFromFormat('d/m/Y', $fields['transit_time_chegada'])->setTime(0,0,0);
            $importacaoEmbarqueObj->transit_time_chegada = $transit_time_chegada;
        }else{
            $importacaoEmbarqueObj->transit_time_chegada = null;
        }
        if(!empty($fields['transit_time_saida'])){
            $transit_time_saida = Carbon::createFromFormat('d/m/Y', $fields['transit_time_saida'])->setTime(0,0,0);
            $importacaoEmbarqueObj->transit_time_saida = $transit_time_saida;
        }else{
            $importacaoEmbarqueObj->transit_time_chegada = null;
        }
        $importacaoEmbarqueObj->save();

        if(!empty($fields['arquivo_carta_programada'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'carta_programa')->count();
            foreach($fields['arquivo_carta_programada'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_carta_programa_".$indice;
                $name_arquivo_carta_programada = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/carta_programada", $name_arquivo_carta_programada);
            
                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_carta_programada;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "carta_programa";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_proforma'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'proforma')->count();
            foreach($fields['arquivo_proforma'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_proforma_".$indice;
                $name_arquivo_proforma = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_proforma);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_proforma;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "proforma";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_conciliator_invoice'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'conciliator_invoice')->count();
            foreach($fields['arquivo_conciliator_invoice'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_conciliator_invoice_".$indice;
                $name_arquivo_conciliator_invoice = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/conciliator_invoice", $name_arquivo_conciliator_invoice);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_conciliator_invoice;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "conciliator_invoice";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_packing_list'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'packing_list')->count();
            foreach($fields['arquivo_packing_list'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_packing_list_".$indice;
                $name_arquivo_packing_list = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/packing_list", $name_arquivo_packing_list);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_packing_list;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "packing_list";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }
        
        if(!empty($fields['arquivo_bl'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'bl')->count();
            foreach($fields['arquivo_bl'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_bl_".$indice;
                $name_arquivo_bl = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/bl", $name_arquivo_bl);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_bl;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "bl";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_contrato_cambio'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'contrato_cambio')->count();
            foreach($fields['arquivo_contrato_cambio'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_contrato_cambio_".$indice;
                $name_arquivo_contrato_cambio = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/contrato_cambio", $name_arquivo_contrato_cambio);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_contrato_cambio;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "contrato_cambio";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_nf_importacao'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'nf_importacao')->count();
            foreach($fields['arquivo_nf_importacao'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_nf_importacao_".$indice;
                $name_arquivo_nf_importacao = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/nf_importacao", $name_arquivo_nf_importacao);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_nf_importacao;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "nf_importacao";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_exoneracao'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'exoneracao')->count();
            foreach($fields['arquivo_exoneracao'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_exoneracao_".$indice;
                $name_arquivo_exoneracao = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/exoneracao", $name_arquivo_exoneracao);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_exoneracao;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "exoneracao";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_nf_remessa'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'nf_remessa')->count();
            foreach($fields['arquivo_nf_remessa'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_nf_remessa_".$indice;
                $name_arquivo_nf_remessa = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/nf_remessa", $name_arquivo_nf_remessa);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_nf_remessa;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "nf_remessa";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_di'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'di')->count();
            foreach($fields['arquivo_di'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_di_".$indice;
                $name_arquivo_di = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/di", $name_arquivo_di);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_di;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "di";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_ci'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'ci')->count();
            foreach($fields['arquivo_ci'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_ci_".$indice;
                $name_arquivo_ci = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_ci);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_ci;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "ci";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_fechamento_processo'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'fechamento_processo')->count();
            foreach($fields['arquivo_fechamento_processo'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_fechamento_processo_".$indice;
                $name_arquivo_fechamento_processo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_fechamento_processo);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_fechamento_processo;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "fechamento_processo";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_armazenagem'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'armazenagem')->count();
            foreach($fields['arquivo_armazenagem'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_armazenagem_".$indice;
                $name_arquivo_armazenagem = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_armazenagem);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_armazenagem;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "armazenagem";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_afrmm'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'afrmm')->count();
            foreach($fields['arquivo_afrmm'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_afrmm_".$indice;
                $name_arquivo_afrmm = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_afrmm);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_afrmm;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "afrmm";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoCustoObj)){
            $importacaoCustoObj = new ImportacaoCusto;
            $importacaoCustoObj->importacaos_id = $importacaoObj->id;
        }

        $importacaoCustoObj->ii = empty($fields['ii'])? null : parserNumber($fields['ii']);
        $importacaoCustoObj->ipi = empty($fields['ipi'])? null : parserNumber($fields['ipi']);
        $importacaoCustoObj->pis = empty($fields['pis'])? null : parserNumber($fields['pis']);
        $importacaoCustoObj->cofins = empty($fields['cofins'])? null : parserNumber($fields['cofins']);
        $importacaoCustoObj->afrmm = empty($fields['afrmm'])? null : parserNumber($fields['afrmm']);
        $importacaoCustoObj->taxa_siscomex = empty($fields['taxa_siscomex'])? null : parserNumber($fields['taxa_siscomex']);
        $importacaoCustoObj->sda = empty($fields['sda'])? null : parserNumber($fields['sda']);
        $importacaoCustoObj->honorarios = empty($fields['honorarios'])? null : parserNumber($fields['honorarios']);
        $importacaoCustoObj->expediente = empty($fields['expediente'])? null : parserNumber($fields['expediente']);
        $importacaoCustoObj->valor_li = empty($fields['valor_li'])? null : parserNumber($fields['valor_li']);
        $importacaoCustoObj->agencia_maritima = empty($fields['agencia_maritima'])? null : parserNumber($fields['agencia_maritima']);
        $importacaoCustoObj->armazem = empty($fields['armazem'])? null : parserNumber($fields['armazem']);
        $importacaoCustoObj->laudo = empty($fields['laudo'])? null : parserNumber($fields['laudo']);
        $importacaoCustoObj->outras_despesas = empty($fields['outras_despesas'])? null : parserNumber($fields['outras_despesas']);
        $importacaoCustoObj->icms_saida = empty($fields['icms_saida'])? null : parserNumber($fields['icms_saida']);
        $importacaoCustoObj->seguro = empty($fields['seguro'])? null : parserNumber($fields['seguro']);
        $importacaoCustoObj->transporte_rodoviario = empty($fields['transporte_rodoviario'])? null : parserNumber($fields['transporte_rodoviario']);
        $importacaoCustoObj->save();

        $importacaoFinanceiroObj = ImportacaoFinanceiro::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoFinanceiroObj)){
            $importacaoFinanceiroObj = new ImportacaoFinanceiro;
            $importacaoFinanceiroObj->importacaos_id = $importacaoObj->id;
        }
        if(!empty($fields['data_embarque_financeiro'])){
            $data_embarque_financeiro = Carbon::createFromFormat('d/m/Y', $fields['data_embarque_financeiro'])->setTime(0,0,0);
            $importacaoFinanceiroObj->data_embarque = $data_embarque_financeiro;
        }else{
            $importacaoFinanceiroObj->data_embarque = null;
        }
        
        $importacaoFinanceiroObj->debito_credito = empty($fields['valor_fob_devido'])? null : parserNumber($fields['valor_fob_devido']);
        $importacaoFinanceiroObj->save();

        $importacaoFollowUpObj = new ImportacaoFollowUp;
        $importacaoFollowUpObj->importacaos_id = $importacaoObj->id;
        $importacaoFollowUpObj->tipo_cor = empty($fields['envio_das_cores'])? null : $fields['envio_das_cores'];
        $importacaoFollowUpObj->envio_cor_data_previsao = empty($fields['envio_das_cores_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_envio = empty($fields['envio_das_cores_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_recebido = empty($fields['envio_das_cores_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_revisao = empty($fields['envio_das_cores_revisao'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_revisao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_aprovacao = empty($fields['aprovacao_quality_sample'])? null : $fields['aprovacao_quality_sample'];
        $importacaoFollowUpObj->quality_sample_data_previsao_envio = empty($fields['quality_sample_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_envio = empty($fields['quality_sample_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_recebido = empty($fields['quality_sample_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_previsao_aprovacao = empty($fields['aprovacao_quality_sample_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_aprovacao = empty($fields['aprovacao_quality_sample_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_realizado'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_transportadora = empty($fields['quality_sample_transportadora'])? null : $fields['quality_sample_transportadora'];
        $importacaoFollowUpObj->quality_sample_awb = empty($fields['quality_sample_awb'])? null : $fields['quality_sample_awb'];
        $importacaoFollowUpObj->laboratorio_aprovacao = empty($fields['aprovacao_laboratorio'])? null : $fields['aprovacao_laboratorio'];
        $importacaoFollowUpObj->laboratorio_data_previsao_envio = empty($fields['laboratorio_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_envio = empty($fields['laboratorio_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_recebido = empty($fields['laboratorio_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_aprovacao = empty($fields['aprovacao_laboratorio_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_laboratorio_realizado'])->setTime(0,0,0);
        $importacaoFollowUpObj->tempo_producao_previsao_termino = empty($fields['tempo_producao_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['tempo_producao_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->tempo_producao_termino = empty($fields['tempo_producao'])? null : Carbon::createFromFormat('d/m/Y', $fields['tempo_producao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_aprovacao = empty($fields['aprovacao_amostra_embarque'])? null : $fields['aprovacao_amostra_embarque'];
        $importacaoFollowUpObj->amostra_embarque_data_previsao_envio = empty($fields['amostra_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_envio = empty($fields['amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_recebido = empty($fields['amostra_embarque_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_previsao_aprovacao = empty($fields['aprovacao_amostra_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_aprovacao = empty($fields['aprovacao_amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_transportadora = empty($fields['amostra_embarque_transportadora'])? null : $fields['amostra_embarque_transportadora'];
        $importacaoFollowUpObj->amostra_embarque_awb = empty($fields['amostra_embarque_awb'])? null : $fields['amostra_embarque_awb'];
        $importacaoFollowUpObj->autorizacao_embarque_data_previsao_envio = empty($fields['autorizacao_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['autorizacao_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->autorizacao_embarque_data_envio = empty($fields['autorizacao_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['autorizacao_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->created_by = Auth::id();
        $importacaoFollowUpObj->save();

        $aprovacao_quality_sample_realizado = empty($fields['aprovacao_quality_sample_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_realizado'])->setTime(0,0,0);
        $aprovacao_laboratorio_realizado = empty($fields['aprovacao_laboratorio_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_laboratorio_realizado'])->setTime(0,0,0);
        $aprovacao_amostra_embarque_enviado = empty($fields['aprovacao_amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_enviado'])->setTime(0,0,0);

        if(!empty($aprovacao_quality_sample_realizado)){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'quality_sample';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_quality_sample'])? null : $fields['aprovacao_quality_sample'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        if(!empty($aprovacao_laboratorio_realizado)){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'laboratorio';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_laboratorio'])? null : $fields['aprovacao_laboratorio'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        if(!empty($aprovacao_amostra_embarque_enviado)){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'amostra_embarque';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_amostra_embarque'])? null : $fields['aprovacao_amostra_embarque'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function modalEditar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $cotacaoDoDiaObj = Cotacoes::wherehas('moeda', function($query){
            $query->where('codigo','220');
        })
        ->orderBy('data', 'desc')->first();
        $importacaoDadoComplemetarFollowUpControllerObj = new ImportacaoDadoComplemetarFollowUpController;
        
        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $importacaoObj = Importacao::find($id);

        if(!empty($importacaoValorPadraoObj)){
            if(empty($importacaoObj->importacao_valor_padraos_id)){
                $importacaoObj->importacao_valor_padraos_id = $importacaoValorPadraoObj->id;
                $importacaoObj->save();

                $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $importacaoObj->id)->first();
                if(empty($importacaoCustoObj)){
                    $importacaoCustoObj = new ImportacaoCusto;
                    $importacaoCustoObj->importacaos_id = $importacaoObj->id;
                }
                
                if(empty($importacaoCustoObj->pis)){
                    $importacaoCustoObj->pis = $importacaoValorPadraoObj->pis;
                }
                if(empty($importacaoCustoObj->cofins)){
                    $importacaoCustoObj->cofins = $importacaoValorPadraoObj->cofins;
                }
                if(empty($importacaoCustoObj->taxa_siscomex)){
                    $importacaoCustoObj->taxa_siscomex = $importacaoValorPadraoObj->taxa_siscomex;
                }
                if(empty($importacaoCustoObj->sda)){
                    $importacaoCustoObj->sda = $importacaoValorPadraoObj->sda;
                }
                if(empty($importacaoCustoObj->honorarios)){
                    $importacaoCustoObj->honorarios = $importacaoValorPadraoObj->honorarios;
                }
                if(empty($importacaoCustoObj->expediente)){
                    $importacaoCustoObj->expediente = $importacaoValorPadraoObj->expediente;
                }
                if(empty($importacaoCustoObj->armazem)){
                    $importacaoCustoObj->armazem = $importacaoValorPadraoObj->armazenagem;
                }
                if(empty($importacaoCustoObj->laudo)){
                    $importacaoCustoObj->laudo = $importacaoValorPadraoObj->laudo;
                }

                $importacaoCustoObj->save();
            }
        }

        $itens = [];
        $index = 0;
        $total = [
            'quantidade' => 0,
            'quantidade_realizada' => 0,
            'preco_fob' => 0,
            'preco_contabil' => 0,
            'diferenca_preco' => 0,
        ];
        if(count($importacaoObj->itens) == 0){
            foreach($importacaoObj->pedidoComprasItens as $item){
                if($item->numero_pedido == $importacaoObj->pedido_compras){
                    $importacaoItemObj = new ImportacaoItem;
                    $importacaoItemObj->importacaos_id = $importacaoObj->id;
                    $importacaoItemObj->produto_codigo = $item->cod_produto;
                    $importacaoItemObj->valor_contabil_unitario = 0;
                    $importacaoItemObj->estabelecimento_codigo = $importacaoObj->estabelecimento_codigo;
                    $importacaoItemObj->numero_proforma = $importacaoObj->numero_proforma;
                    $importacaoItemObj->pedido_compras = $item->numero_pedido;
                    $importacaoItemObj->nota_uuid_nasajon = $item->id_nota;
                    $importacaoItemObj->save();
              }
            }
        }

        foreach($importacaoObj->pedidoComprasItens as $item){       
            $index++;
            if(isset($item->produto->foto) && Storage::exists('public/produto_fotos/' . $item->produto->foto->filename) && Storage::exists('public/produto_fotos/' . $item->produto->foto->thumb_filename)){
                $foto= "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $item->produto->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $item->produto->foto->filename) . "\" class=\"btn-foto-estoque thumb ml-2 mt-1\"></a>"; 
            }
            else{
                $foto = "";
            }

            $itens[] = [
                'item' => $index,
                'codigo' => $item->cod_produto,
                'produto' => empty($item->produto)? '' : $item->produto->descricao,
                'composicao' => $item->produtoNasajon->composicao,
                'gramatura_gm2' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->gramatura_gm2,
                'largura' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->largura,
                'gramatura_gml' => '',
                'rendimento' => '',
                'instrucao_lavagem' => '',
                'quantidade' => parserValor4CasasDecimais($item->quantidade),
                'quantidade_realizada' => $item->situacao_item == 'Cancelado'? '' : parserValor4CasasDecimais($item->quantidade - $item->quantidade_restante),
                'preco_unitario' => parserValor4CasasDecimais($item->preco_compra_unitario),
                'preco_total' => parserValor4CasasDecimais($item->quantidade * $item->preco_compra_unitario),
                'foto' => $foto,
                'status' => $item->situacao_item,
                'valor_contabil_unitario' => empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario),
                'valor_contabil_total' =>  empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade),
            ];

            $total['quantidade'] += $item->quantidade;
            $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
            $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
            if(!empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
            }
            
        }

        $aprovacao_amostra_embarque = '';
        if(!empty($importacaoObj->aprovacao_amostra_embarque)){
            $aprovacao_amostra_embarque = $importacaoObj->aprovacao_amostra_embarque? 0 : 1;
        }

        $aprovado_embarque_produto_codigo = '';
        $aprovado_embarque_produto = '';
        if(!empty($importacaoObj->aprovadoEmbarqueProdutoDetalhes)){
            $aprovado_embarque_produto_codigo = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->codigo_produto;
            $aprovado_embarque_produto = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->descricao;
        }

        $arquivo_carta_programada = '';
        if(!empty($importacaoObj->arquivo_carta_programada)){
            $arquivo_carta_programada = Storage::url($importacaoObj->arquivo_carta_programada);
        }
        
        $porto_origem = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->porto_origem;
        $porto_destino = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->porto_destino;
        $agente_compra = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->agente_compra;
        $armador = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->armador;
        $etd_booking = empty($importacaoObj->embarqueDetalhes)? false : $importacaoObj->embarqueDetalhes->etd_booking;
        $eta_booking = empty($importacaoObj->embarqueDetalhes)? false : $importacaoObj->embarqueDetalhes->eta_booking;
        $numero_bl = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->numero_bl;
        $transit_time_chegada = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->transit_time_chegada;
        $transit_time_saida = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->transit_time_saida;

        $arquivos = [];
        foreach($importacaoObj->documentos as $documento){
            $arquivos['arquivo_'.$documento->tipo][] = Storage::url($documento->caminho);
        }

        $ii = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->ii);
        $ipi = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->ipi);
        $pis = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->pis);
        $cofins = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->cofins);
        $afrmm = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->afrmm);
        $taxa_siscomex = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->taxa_siscomex);
        $sda = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->sda);
        $honorarios = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->honorarios);
        $expediente = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->expediente);
        $valor_li = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->valor_li);
        $agencia_maritima = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->agencia_maritima);
        $armazem = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->armazem);
        $laudo = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->laudo);
        $outras_despesas = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->outras_despesas);
        $icms_saida = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->icms_saida);
        $seguro = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->seguro);
        $transporte_rodoviario = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->transporte_rodoviario);

        $data_embarque = empty($importacaoObj->financeiro)? '' : $importacaoObj->financeiro->data_embarque;
        $debito_credito = empty($importacaoObj->financeiro)? '' : $importacaoObj->financeiro->debito_credito;

        if($importacaoObj->pago){
            $valor_fob_pago = $total['preco_fob'];
        }else if(!empty($importacaoObj->financeiro)){
            if(!empty($importacaoObj->financeiro->lancamentos)){
                $valor_fob_pago = empty($importacaoObj->financeiro->valorCambioDolarTotal->total)? 0 : $importacaoObj->financeiro->valorCambioDolarTotal->total;
            }else{
                $valor_fob_pago = 0;
            }
        }else{
            $valor_fob_pago = 0;
        }

        $outras_despesas = [];
        $outras_despesas_total = 0;
        foreach($importacaoObj->custosOutraDespesa as $custo_outra_depesa){
            $outras_despesas[] = [
                'id' => encrypt($custo_outra_depesa->id),
                'descricao' => $custo_outra_depesa->descricao,
                'valor' => parserValor($custo_outra_depesa->valor),
            ];

            $outras_despesas_total += $custo_outra_depesa->valor;
        }

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $total['preco_fob'] * $importacaoObj->valorPadrao->dolar_referencia;
        $total_cambio_realizado = empty($importacaoObj->financeiro->valorCambioTotal->total)? '' : parserValor($importacaoObj->financeiro->valorCambioTotal->total);

        $ii_previsto = $total_cambio_previsto * 26 / 100;
        $pis_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->pis / 100;
        $cofins_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->cofins / 100;
        $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
        $agencia_maritima_previsto = empty($importacaoValorPadraoObj->agencia_maritima)? 0 : $importacaoValorPadraoObj->agencia_maritima;
        $afrmm_previsto = $agencia_maritima_previsto * 0.25;

        $icms_saida_previsto = $total_cambio_previsto + $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $agencia_maritima_previsto;
        $icms_saida_previsto = (($icms_saida_previsto / 0.88 ) + (($icms_saida_previsto / 0.88 )* 0.30)) * (4/100);
        
        $icms_a_pagar_previsto = $total_cambio_previsto + $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $agencia_maritima_previsto;
        $icms_a_pagar_previsto = (($icms_saida_previsto / 0.88 ) + (($icms_saida_previsto / 0.88 )* 0.30)) * 0.006;

        $icms_a_pagar = parserNumber($total_cambio_realizado) + (($total['preco_fob'] - $valor_fob_pago) * $importacaoValorPadraoObj->dolar_referencia) + parserNumber($ii) + parserNumber($pis) + parserNumber($cofins) + parserNumber($taxa_siscomex) + parserNumber($afrmm) + parserNumber($agencia_maritima);
        $icms_a_pagar = (($icms_a_pagar / 0.88 ) + (($icms_a_pagar / 0.88 )* 0.30)) * 0.006;

        $custo_previsto = [
            'total_cambio_previsto' => empty($total_cambio_previsto)? '' : parserValor($total_cambio_previsto),
            'ii_previsto' => empty($ii_previsto)? '' : parserValor($ii_previsto),
            'ipi_previsto' => empty($importacaoObj->valorPadrao->ipi)? '' : parserValor($importacaoObj->valorPadrao->ipi),
            'pis_previsto' => empty($pis_previsto)? '' : parserValor($pis_previsto),
            'cofins_previsto' => empty($cofins_previsto)? '' : parserValor($cofins_previsto),
            'afrmm_previsto' => empty($afrmm_previsto)? '' : parserValor($afrmm_previsto),
            'taxa_siscomex_previsto' => empty($importacaoObj->valorPadrao->taxa_siscomex)? '' : parserValor($importacaoObj->valorPadrao->taxa_siscomex),
            'sda_previsto' => empty($importacaoObj->valorPadrao->sda)? '' : parserValor($importacaoObj->valorPadrao->sda),
            'honorarios_previsto' => empty($importacaoObj->valorPadrao->honorarios)? '' : parserValor($importacaoObj->valorPadrao->honorarios),
            'expediente_previsto' => empty($importacaoObj->valorPadrao->expediente)? '' : parserValor($importacaoObj->valorPadrao->expediente),
            'valor_li_previsto' => empty($importacaoObj->valorPadrao->valor_li)? '' : parserValor($importacaoObj->valorPadrao->valor_li),
            'agencia_maritima_previsto' => empty($importacaoObj->valorPadrao->agencia_maritima)? '' : parserValor($importacaoObj->valorPadrao->agencia_maritima),
            'laudo_previsto' => empty($importacaoObj->valorPadrao->laudo)? '' : parserValor($importacaoObj->valorPadrao->laudo),
            'seguro_previsto' => empty($importacaoObj->valorPadrao->seguro)? '' : parserValor($importacaoObj->valorPadrao->seguro),
            'armazenagem_previsto' => empty($importacaoObj->valorPadrao->armazenagem)? '' : parserValor($importacaoObj->valorPadrao->armazenagem),
            'outras_despesas_previsto' => empty($importacaoObj->valorPadrao->outras_despesas)? '' : parserValor($importacaoObj->valorPadrao->outras_despesas),
            'total_previsto' => empty($importacaoObj->valorPadraoTotal->total)? '' : parserValor($importacaoObj->valorPadraoTotal->total+$total_cambio_previsto+$pis_previsto+$cofins_previsto),
            'dolar_referencia' => empty($importacaoValorPadraoObj->dolar_referencia)? '' : parserValor($importacaoValorPadraoObj->dolar_referencia),
            'transporte_rodoviario_previsto' => empty($importacaoValorPadraoObj->frete_rodoviario)? '' : parserValor($importacaoValorPadraoObj->frete_rodoviario),
            'icms_saida_previsto' => empty($icms_saida_previsto)? '' : parserValor($icms_saida_previsto),
            'icms_a_pagar_previsto' => empty($icms_a_pagar)? '' : parserValor($icms_a_pagar),
        ];

        if($total['preco_fob'] > $total['preco_contabil']){
            $total['diferenca_preco'] = parserValor($total['preco_fob'] - $total['preco_contabil']);
        }else{
            $total['diferenca_preco'] = empty($total['preco_contabil'] - $total['preco_fob'])? '' : parserValor($total['preco_contabil'] - $total['preco_fob']);
        }
        $total['quantidade'] = empty($total['quantidade'])? '' : parserValor($total['quantidade']);
        $total['quantidade_realizada'] = empty($total['quantidade_realizada'])? '' : parserValor($total['quantidade_realizada']);
        $total['preco_fob'] = empty($total['preco_fob'])? '' : parserValor($total['preco_fob']);
        $total['preco_contabil'] = empty($total['preco_contabil'])? '' : parserValor($total['preco_contabil']);

        $dados_follow = [
            'tipo_cor' => '',
            'envio_cor_data_previsao' => '',
            'envio_cor_data_envio' => '',
            'envio_cor_data_recebido' => '',
            'quality_sample_aprovacao' => '',
            'quality_sample_data_previsao_envio' => '',
            'quality_sample_data_envio' => '',
            'quality_sample_data_recebido' => '',
            'quality_sample_data_previsao_aprovacao' => '',
            'quality_sample_data_aprovacao' => '',
            'laboratorio_aprovacao' => '',
            'laboratorio_data_previsao_envio' => '',
            'laboratorio_data_envio' => '',
            'laboratorio_data_recebido' => '',
            'laboratorio_data_aprovacao' => '',
            'tempo_producao_previsao_termino' => '',
            'tempo_producao_termino' => '',
            'amostra_embarque_aprovacao' => '',
            'amostra_embarque_data_previsao_envio' => '',
            'amostra_embarque_data_envio' => '',
            'amostra_embarque_data_recebido' => '',
            'amostra_embarque_data_previsao_aprovacao' => '',
            'amostra_embarque_data_aprovacao' => '',
            'autorizacao_embarque_data_previsao_envio' => '',
            'autorizacao_embarque_data_envio' => '',
            'envio_cor_data_revisao' => '',
            'quality_sample_transportadora' => '',
            'quality_sample_awb' => '',
            'amostra_embarque_transportadora' => '',
            'amostra_embarque_awb' => '', 
        ];

        if(empty($importacaoObj->followUpDetalhes)){
            $importacaoFollowUpObj = new ImportacaoFollowUp;
            $importacaoFollowUpObj->importacaos_id = $id;
            $importacaoFollowUpObj->created_by = Auth::id();
            $importacaoFollowUpObj->save();
        }else{
            $importacaoFollowUpObj = $importacaoObj->followUpDetalhes;
            $dados_follow['tipo_cor'] = empty($importacaoObj->followUpDetalhes->tipo_cor)? '' : $importacaoObj->followUpDetalhes->tipo_cor; 
            $dados_follow['envio_cor_data_previsao'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_previsao)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_previsao->format('d/m/Y'); 
            $dados_follow['envio_cor_data_envio'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_envio)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_envio->format('d/m/Y'); 
            $dados_follow['envio_cor_data_recebido'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_recebido)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_recebido->format('d/m/Y'); 
            $dados_follow['quality_sample_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_aprovacao; 
            $dados_follow['quality_sample_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['quality_sample_data_envio'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_envio)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_envio->format('d/m/Y'); 
            $dados_follow['quality_sample_data_recebido'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_recebido)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_recebido->format('d/m/Y'); 
            $dados_follow['quality_sample_data_previsao_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_previsao_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_previsao_aprovacao->format('d/m/Y'); 
            $dados_follow['quality_sample_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->quality_sample_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->quality_sample_data_aprovacao->format('d/m/Y'); 
            $dados_follow['laboratorio_aprovacao'] = empty($importacaoObj->followUpDetalhes->laboratorio_aprovacao)? '' : $importacaoObj->followUpDetalhes->laboratorio_aprovacao; 
            $dados_follow['laboratorio_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['laboratorio_data_envio'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_envio)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_envio->format('d/m/Y'); 
            $dados_follow['laboratorio_data_recebido'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_recebido)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_recebido->format('d/m/Y'); 
            $dados_follow['laboratorio_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->laboratorio_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->laboratorio_data_aprovacao->format('d/m/Y'); 
            $dados_follow['tempo_producao_previsao_termino'] = empty($importacaoObj->followUpDetalhes->tempo_producao_previsao_termino)? '' : $importacaoObj->followUpDetalhes->tempo_producao_previsao_termino->format('d/m/Y'); 
            $dados_follow['tempo_producao_termino'] = empty($importacaoObj->followUpDetalhes->tempo_producao_termino)? '' : $importacaoObj->followUpDetalhes->tempo_producao_termino->format('d/m/Y'); 
            $dados_follow['amostra_embarque_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_aprovacao; 
            $dados_follow['amostra_embarque_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_envio'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_envio)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_envio->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_recebido'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_recebido)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_recebido->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_previsao_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'); 
            $dados_follow['amostra_embarque_data_aprovacao'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_data_aprovacao)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_data_aprovacao->format('d/m/Y'); 
            $dados_follow['autorizacao_embarque_data_previsao_envio'] = empty($importacaoObj->followUpDetalhes->autorizacao_embarque_data_previsao_envio)? '' : $importacaoObj->followUpDetalhes->autorizacao_embarque_data_previsao_envio->format('d/m/Y'); 
            $dados_follow['autorizacao_embarque_data_envio'] = empty($importacaoObj->followUpDetalhes->autorizacao_embarque_data_envio)? '' : $importacaoObj->followUpDetalhes->autorizacao_embarque_data_envio->format('d/m/Y'); 
            $dados_follow['envio_cor_data_revisao'] = empty($importacaoObj->followUpDetalhes->envio_cor_data_revisao)? '' : $importacaoObj->followUpDetalhes->envio_cor_data_revisao->format('d/m/Y');
            $dados_follow['quality_sample_transportadora'] = empty($importacaoObj->followUpDetalhes->quality_sample_transportadora)? '' : $importacaoObj->followUpDetalhes->quality_sample_transportadora;
            $dados_follow['quality_sample_awb'] = empty($importacaoObj->followUpDetalhes->quality_sample_awb)? '' : $importacaoObj->followUpDetalhes->quality_sample_awb;
            $dados_follow['amostra_embarque_transportadora'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_transportadora)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_transportadora;
            $dados_follow['amostra_embarque_awb'] = empty($importacaoObj->followUpDetalhes->amostra_embarque_awb)? '' : $importacaoObj->followUpDetalhes->amostra_embarque_awb; 
        }
        $nota_importacao_numero = '';
        $nota_remessa_numero = '';
        if(!empty($importacaoObj->pedidoCompras->notas[0])){
            if(!empty($importacaoObj->pedidoCompras->notas[0]->notasentradas[0])){
                $nota_importacao_numero = $importacaoObj->pedidoCompras->notas[0]->notasentradas[0]["Número do Documento"];

                if(!empty($importacaoObj->pedidoCompras->notas[0]->notasentradas[0]->documentosAssociacoes[0])){
                    $nota_saida_obj = $importacaoObj->pedidoCompras->notas[0]->notasentradas[0]->documentosAssociacoes[0]->notaSaida;
                    $nota_remessa_numero = $nota_saida_obj->numero;

                    $icms_a_pagar = ($nota_saida_obj->valor * (0.6/100));
                    $icms_saida = parserValor($nota_saida_obj->valor * (4/100));
                }
            }
        }      

        $dados = [
            'id' => encrypt($id),
            'fornecedor' => trim($importacaoObj->fornecedor->nome).' - '.$importacaoObj->fornecedor->cnpj_cpf,
            'proforma' => $importacaoObj->numero_proforma,
            'pcmn' => $importacaoObj->pedido_compras,
            'referencia' => $importacaoObj->referencia,
            'data_proforma' => parserData($importacaoObj->data_proforma),
            'data_previsao_carta_programa' => empty($importacaoObj->data_previsao_carta_programa)? '' : parserData($importacaoObj->data_previsao_carta_programa),
            'id_nota' => encrypt($importacaoObj->pedidoCompras->id_nota),
            'estabelecimento_codigo' => $importacaoObj->estabelecimento_codigo,
            'data_previsao_recebimento' => parserData($importacaoObj->pedidoCompras->previsao_entrega),
            'respresentante' => empty($importacaoObj->respresentante)? '' : $importacaoObj->respresentante->nome.' - '.$importacaoObj->respresentante->cnpj_cpf,
            'itens' => $itens,
            'data_carga_pronta_previsao' => empty($importacaoObj->data_carga_pronta_previsao)? '' : parserData($importacaoObj->data_carga_pronta_previsao),
            'data_carga_pronta_realizado' => empty($importacaoObj->data_carga_pronta_realizado)? '' : parserData($importacaoObj->data_carga_pronta_realizado),
            'data_embarque_previsao' => empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao),
            'data_embarque_realizado' => empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado),
            'data_chegada_porto_previsao' => empty($importacaoObj->data_chegada_porto_previsao)? '' : parserData($importacaoObj->data_chegada_porto_previsao),
            'data_chegada_porto_realizado' => empty($importacaoObj->data_chegada_porto_realizado)? '' : parserData($importacaoObj->data_chegada_porto_realizado),
            'data_di_previsao' => empty($importacaoObj->data_di_previsao)? '' : parserData($importacaoObj->data_di_previsao),
            'data_di_realizado' => empty($importacaoObj->data_di_realizado)? '' : parserData($importacaoObj->data_di_realizado),
            'data_devolucao_cntr_previsao' => empty($importacaoObj->data_devolucao_cntr_previsao)? '' : parserData($importacaoObj->data_devolucao_cntr_previsao),
            'data_devolucao_cntr_realizado' => empty($importacaoObj->data_devolucao_cntr_realizado)? '' : parserData($importacaoObj->data_devolucao_cntr_realizado),
            'data_quality_sample_enviado' => empty($importacaoObj->data_quality_sample_enviado)? '' : parserData($importacaoObj->data_quality_sample_enviado),
            'data_quality_sample_recebido' => empty($importacaoObj->data_quality_sample_recebido)? '' : parserData($importacaoObj->data_quality_sample_recebido),
            'data_handlooms_enviado' => empty($importacaoObj->data_handlooms_enviado)? '' : parserData($importacaoObj->data_handlooms_enviado),
            'data_handlooms_recebido' => empty($importacaoObj->data_handlooms_recebido)? '' : parserData($importacaoObj->data_handlooms_recebido),
            'data_strike_off_enviado' => empty($importacaoObj->data_strike_off_enviado)? '' : parserData($importacaoObj->data_strike_off_enviado),
            'data_strike_off_recebido' => empty($importacaoObj->data_strike_off_recebido)? '' : parserData($importacaoObj->data_strike_off_recebido),
            'data_amostra_embarque_enviado' => empty($importacaoObj->data_amostra_embarque_enviado)? '' : parserData($importacaoObj->data_amostra_embarque_enviado),
            'data_amostra_embarque_recebido' => empty($importacaoObj->data_amostra_embarque_recebido)? '' : parserData($importacaoObj->data_amostra_embarque_recebido),
            'aprovacao_amostra_embarque' => $aprovacao_amostra_embarque,
            'aprovado_embarque_produto_codigo' => $aprovado_embarque_produto_codigo,
            'aprovado_embarque_produto' => $aprovado_embarque_produto,
            'tempo_producao' => empty($importacaoObj->tempo_producao)? '' : $importacaoObj->tempo_producao,
            'arquivo_carta_programada' => $arquivo_carta_programada,
            'total' => $total,
            'porto_origem' => empty($porto_origem)? '' : $porto_origem,
            'porto_destino' => empty($porto_destino)? '' : $porto_destino,
            'agente_compra' => empty($agente_compra)? '' : $agente_compra,
            'armador' => empty($armador)? '' : $armador,
            'etd_booking' => $etd_booking,
            'eta_booking' => $eta_booking,
            'numero_bl' => empty($numero_bl)? '' : $numero_bl,
            'transit_time_chegada' => empty($transit_time_chegada)? '' : parserData($transit_time_chegada),
            'transit_time_saida' => empty($transit_time_saida)? '' : parserData($transit_time_saida),
            'arquivos' => $arquivos,
            'ii' => empty($ii)? '' : $ii,
            'ipi' => empty($ipi)? '' : $ipi,
            'pis' => empty($pis)? '' : $pis,
            'cofins' => empty($cofins)? '' : $cofins,
            'afrmm' => empty($afrmm)? '' : $afrmm,
            'taxa_siscomex' => empty($taxa_siscomex)? '' : $taxa_siscomex,
            'sda' => empty($sda)? '' : $sda,
            'honorarios' => empty($honorarios)? '' : $honorarios,
            'expediente' => empty($expediente)? '' : $expediente,
            'valor_li' => empty($valor_li)? '' : $valor_li,
            'agencia_maritima' => empty($agencia_maritima)? '' : $agencia_maritima,
            'armazem' => empty($armazem)? '' : $armazem,
            'laudo' => empty($laudo)? '' : $laudo,
            'outras_despesas' => empty($outras_despesas)? '' : $outras_despesas,
            'icms_saida' => empty($icms_saida)? '' : $icms_saida,
            'seguro' => empty($seguro)? '' : $seguro,
            'transporte_rodoviario' => empty($transporte_rodoviario)? '' : $transporte_rodoviario,
            'data_embarque' => empty($data_embarque)? '' : parserData($data_embarque),
            'debito_credito' => empty($debito_credito)? '' : parserValor($debito_credito),
            'valor_fob_pago' => parserValor($valor_fob_pago),
            'valor_fob_devido' => parserNumber($total['preco_fob'])-$valor_fob_pago <= 0? 0 : parserValor(parserNumber($total['preco_fob'])-$valor_fob_pago),
            'outras_despesas' => $outras_despesas,
            'outras_despesas_total' => parserValor($outras_despesas_total),
            'custo_previsto' => $custo_previsto,
            'total_cambio_realizado' => $total_cambio_realizado,
            'dados_follow' => $dados_follow,
            'tipos_cores' => $importacaoDadoComplemetarFollowUpControllerObj->dadosTipoCores(),
            'tipo_aprovacoes_simples' => $importacaoDadoComplemetarFollowUpControllerObj->dadosSimplesAprovacao(),
            'tipo_aprovacoes_parcial' => $importacaoDadoComplemetarFollowUpControllerObj->dadosAprovacaoParcial(),
            'id_follow_up' => encrypt($importacaoFollowUpObj->id),
            'icms_a_pagar' => empty($icms_a_pagar)? '' : parserValor($icms_a_pagar),
            'nota_importacao_numero' => $nota_importacao_numero,
            'nota_remessa_numero' => $nota_remessa_numero,
            'cotacao_dolar' => empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $importacaoValorPadraoObj->dolar_referencia,
            'frete_fornecedor' => empty($importacaoObj->frete_fornecedor)? '' : parserValor($importacaoObj->frete_fornecedor),
        ];
     
        return view('programs.importacao.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ImportacaoRequest $request){
        ini_set('memory_limit','80M');
        ini_set('post_max_size', '50M');
        $fields = $request->only('id', 'fornecedor', 'numero_proforma', 'pedido_compras', 'referencia', 'data_proforma', 'previsao_carta_programa', 'respresentante', 'carga_pronta_previsao', 'carga_pronta_realizado', 'embarque_previsao', 'embarque_realizado', 'chegada_porto_previsao', 'chegada_porto_realizado', 'data_di_previsao', 'data_di_realizado', 'devolucao_cntr_previsao', 'devolucao_cntr_realizado', 'quality_sample_enviado', 'quality_sample_recebido', 'handlooms_enviado', 'handlooms_recebido', 'strike_off_enviado', 'strike_off_recebido', 'amostra_embarque_enviado', 'amostra_embarque_recebido', 'aprovacao_amostra_embarque', 'aprovado_embarque_produto_codigo', 'aprovado_embarque_produto', 'tempo_producao', 'valor_contabil_unitario', 'porto_origem','porto_destino','agente_compra','armador','etd_booking','eta_booking','numero_bl','transit_time_chegada','transit_time_saida','arquivo_carta_programada', 'arquivo_proforma','arquivo_conciliator_invoice','arquivo_packing_list','arquivo_bl','arquivo_contrato_cambio','arquivo_nf_importacao','arquivo_exoneracao','arquivo_nf_remessa','arquivo_di','arquivo_ci','ii','ipi','pis','cofins','afrmm','taxa_siscomex','sda','honorarios','expediente','valor_li','agencia_maritima','armazem','laudo','outras_despesas','icms_saida','seguro', 'debito_credito_financeiro', 'data_embarque_financeiro', 'arquivo_fechamento_processo', 'transporte_rodoviario', 'arquivo_armazenagem', 'arquivo_afrmm',"envio_das_cores","envio_das_cores_previsao","envio_das_cores_enviado","envio_das_cores_recebido","aprovacao_quality_sample","quality_sample_previsao","quality_sample_enviado","quality_sample_recebido","aprovacao_quality_sample_previsao","aprovacao_quality_sample_realizado","aprovacao_laboratorio","laboratorio_previsao","laboratorio_enviado","laboratorio_recebido","aprovacao_laboratorio_realizado","tempo_producao_previsao","tempo_producao","aprovacao_amostra_embarque","amostra_embarque_previsao","amostra_embarque_enviado","amostra_embarque_recebido","aprovacao_amostra_embarque_previsao","aprovacao_amostra_embarque_enviado","autorizacao_embarque_previsao","autorizacao_embarque_enviado","envio_das_cores_revisao","quality_sample_transportadora", "quality_sample_awb","amostra_embarque_transportadora","amostra_embarque_awb");

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor = $fornecedor->first();

        $proforma = ComprasNasajon::select();
        $proforma->where('proforma', 'ILIKE', ($fields['numero_proforma']));
        $proforma->where('estabelecimento', '03');
        $proforma = $proforma->first();

        $respresentante = FornecedorNasajon::select();
        $respresentante->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['respresentante']));
        $respresentante = $respresentante->first();

        $importacaoObj = Importacao::find($id);
        $importacaoObj->estabelecimento_codigo = $proforma->estabelecimento;
        $importacaoObj->fornecedor_codigo = $fornecedor->codigo;
        $importacaoObj->numero_proforma = $proforma->proforma;
        $importacaoObj->pedido_compras = $proforma->numero_pedido;
        $importacaoObj->referencia = $fields['referencia'];
        $importacaoObj->data_proforma = $proforma->data_compra;
        if(!empty($fields['previsao_carta_programa'])){
            $data_previsao_carta_programa = Carbon::createFromFormat('d/m/Y', $fields['previsao_carta_programa'])->setTime(0,0,0);
            $importacaoObj->data_previsao_carta_programa = $data_previsao_carta_programa;
        }
        $importacaoObj->data_previsao_recebimento = $proforma->previsao_entrega;
        if(!empty($fields['respresentante'])){
            $respresentante = FornecedorNasajon::select();
            $respresentante->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['respresentante']));
            $respresentante = $respresentante->first();

            $importacaoObj->respresentante_codigo = $respresentante->codigo;
        }
        if(!empty($fields['carga_pronta_previsao'])){
            $data_carga_pronta_previsao = Carbon::createFromFormat('d/m/Y', $fields['carga_pronta_previsao'])->setTime(0,0,0);
            $importacaoObj->data_carga_pronta_previsao = $data_carga_pronta_previsao;
        }else{
            $importacaoObj->data_carga_pronta_previsao = null;
        }
        if(!empty($fields['carga_pronta_realizado'])){
            $data_carga_pronta_realizado = Carbon::createFromFormat('d/m/Y', $fields['carga_pronta_realizado'])->setTime(0,0,0);
            $importacaoObj->data_carga_pronta_realizado = $data_carga_pronta_realizado;
        }else{
            $importacaoObj->data_carga_pronta_realizado = null;
        }
        if(!empty($fields['embarque_previsao'])){
            $data_embarque_previsao = Carbon::createFromFormat('d/m/Y', $fields['embarque_previsao'])->setTime(0,0,0);
            $importacaoObj->data_embarque_previsao = $data_embarque_previsao;
        }else{
            $importacaoObj->data_embarque_previsao = null;
        }
        if(!empty($fields['embarque_realizado'])){
            $data_embarque_realizado = Carbon::createFromFormat('d/m/Y', $fields['embarque_realizado'])->setTime(0,0,0);
            $importacaoObj->data_embarque_realizado = $data_embarque_realizado;
        }else{
            $importacaoObj->data_embarque_realizado = null;
        }
        if(!empty($fields['chegada_porto_previsao'])){
            $data_chegada_porto_previsao = Carbon::createFromFormat('d/m/Y', $fields['chegada_porto_previsao'])->setTime(0,0,0);
            $importacaoObj->data_chegada_porto_previsao = $data_chegada_porto_previsao;
        }else{
            $importacaoObj->data_chegada_porto_previsao = null;
        }
        if(!empty($fields['chegada_porto_realizado'])){
            $data_chegada_porto_realizado = Carbon::createFromFormat('d/m/Y', $fields['chegada_porto_realizado'])->setTime(0,0,0);
            $importacaoObj->data_chegada_porto_realizado = $data_chegada_porto_realizado;
        }else{
            $importacaoObj->data_chegada_porto_realizado = null;
        }
        if(!empty($fields['data_di_previsao'])){
            $data_di_previsao = Carbon::createFromFormat('d/m/Y', $fields['data_di_previsao'])->setTime(0,0,0);
            $importacaoObj->data_di_previsao = $data_di_previsao;
        }else{
            $importacaoObj->data_di_previsao = null;
        }
        if(!empty($fields['data_di_realizado'])){
            $data_di_realizado = Carbon::createFromFormat('d/m/Y', $fields['data_di_realizado'])->setTime(0,0,0);
            $importacaoObj->data_di_realizado = $data_di_realizado;
        }else{
            $importacaoObj->data_di_realizado = null;
        }
        if(!empty($fields['devolucao_cntr_previsao'])){
            $data_devolucao_cntr_previsao = Carbon::createFromFormat('d/m/Y', $fields['devolucao_cntr_previsao'])->setTime(0,0,0);
            $importacaoObj->data_devolucao_cntr_previsao = $data_devolucao_cntr_previsao;
        }else{
            $importacaoObj->data_devolucao_cntr_previsao = null;
        }
        if(!empty($fields['devolucao_cntr_realizado'])){
            $data_devolucao_cntr_realizado = Carbon::createFromFormat('d/m/Y', $fields['devolucao_cntr_realizado'])->setTime(0,0,0);
            $importacaoObj->data_devolucao_cntr_realizado = $data_devolucao_cntr_realizado;
        }else{
            $importacaoObj->data_devolucao_cntr_realizado = null;
        }
        if(!empty($fields['quality_sample_enviado'])){
            $data_quality_sample_enviado = Carbon::createFromFormat('d/m/Y', $fields['quality_sample_enviado'])->setTime(0,0,0);
            $importacaoObj->data_quality_sample_enviado = $data_quality_sample_enviado;
        }else{
            $importacaoObj->data_quality_sample_enviado = null;
        }
        if(!empty($fields['quality_sample_recebido'])){
            $data_quality_sample_recebido = Carbon::createFromFormat('d/m/Y', $fields['quality_sample_recebido'])->setTime(0,0,0);
            $importacaoObj->data_quality_sample_recebido = $data_quality_sample_recebido;
        }else{
            $importacaoObj->data_quality_sample_recebido = null;
        }
        if(!empty($fields['handlooms_enviado'])){
            $data_handlooms_enviado = Carbon::createFromFormat('d/m/Y', $fields['handlooms_enviado'])->setTime(0,0,0);
            $importacaoObj->data_handlooms_enviado = $data_handlooms_enviado;
        }else{
            $importacaoObj->data_handlooms_enviado = null;
        }
        if(!empty($fields['handlooms_recebido'])){
            $data_handlooms_recebido = Carbon::createFromFormat('d/m/Y', $fields['handlooms_recebido'])->setTime(0,0,0);
            $importacaoObj->data_handlooms_recebido = $data_handlooms_recebido;
        }else{
            $importacaoObj->data_handlooms_recebido = null;
        }
        if(!empty($fields['strike_off_enviado'])){
            $data_strike_off_enviado = Carbon::createFromFormat('d/m/Y', $fields['strike_off_enviado'])->setTime(0,0,0);
            $importacaoObj->data_strike_off_enviado = $data_strike_off_enviado;
        }else{
            $importacaoObj->data_strike_off_enviado = null;
        }
        if(!empty($fields['strike_off_recebido'])){
            $data_strike_off_recebido = Carbon::createFromFormat('d/m/Y', $fields['strike_off_recebido'])->setTime(0,0,0);
            $importacaoObj->data_strike_off_recebido = $data_strike_off_recebido;
        }else{
            $importacaoObj->data_strike_off_recebido = null;
        }
        if(!empty($fields['amostra_embarque_enviado'])){
            $data_amostra_embarque_enviado = Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_enviado'])->setTime(0,0,0);
            $importacaoObj->data_amostra_embarque_enviado = $data_amostra_embarque_enviado;
        }else{
            $importacaoObj->data_amostra_embarque_enviado = null;
        }
        if(!empty($fields['amostra_embarque_recebido'])){
            $data_amostra_embarque_recebido = Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_recebido'])->setTime(0,0,0);
            $importacaoObj->data_amostra_embarque_recebido = $data_amostra_embarque_recebido;
        }else{
            $importacaoObj->data_amostra_embarque_recebido = null;
        }
        $importacaoObj->aprovacao_amostra_embarque = $fields['aprovacao_amostra_embarque'] == 0? true : false;
        if(!empty($fields['aprovado_embarque_produto_codigo'])){
            $produto = ProdutoEspecificacao::select();
            $produto->where('codigo_produto', 'ILIKE', ($fields['aprovado_embarque_produto_codigo']));
            $produto = $produto->first();

            $importacaoObj->aprovado_embarque_produto_codigo = $produto->codigo_produto;
        }else{
            $importacaoObj->aprovado_embarque_produto_codigo = null;
        }
        if(!empty($fields['tempo_producao'])){
            $importacaoObj->tempo_producao = intval($fields['tempo_producao']);
        }else{
            $importacaoObj->tempo_producao = null;
        }
        $importacaoObj->updated_by = Auth::id();
        $importacaoObj->save();

        $importacaoEmbarqueObj = ImportacaoEmbarque::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoEmbarqueObj)){
            $importacaoEmbarqueObj = new ImportacaoEmbarque;
            $importacaoEmbarqueObj->importacaos_id = $importacaoObj->id;
        }
        
        $importacaoEmbarqueObj->porto_origem = $fields['porto_origem'];
        $importacaoEmbarqueObj->porto_destino = $fields['porto_destino'];
        $importacaoEmbarqueObj->agente_compra = $fields['agente_compra'];
        $importacaoEmbarqueObj->armador = $fields['armador'];
        $importacaoEmbarqueObj->etd_booking = !isset($fields['etd_booking'])? false : true;
        $importacaoEmbarqueObj->eta_booking = !isset($fields['eta_booking'])? false : true;
        $importacaoEmbarqueObj->numero_bl = $fields['numero_bl'];
        if(!empty($fields['transit_time_chegada'])){
            $transit_time_chegada = Carbon::createFromFormat('d/m/Y', $fields['transit_time_chegada'])->setTime(0,0,0);
            $importacaoEmbarqueObj->transit_time_chegada = $transit_time_chegada;
        }else{
            $importacaoEmbarqueObj->transit_time_chegada = null;
        }
        if(!empty($fields['transit_time_saida'])){
            $transit_time_saida = Carbon::createFromFormat('d/m/Y', $fields['transit_time_saida'])->setTime(0,0,0);
            $importacaoEmbarqueObj->transit_time_saida = $transit_time_saida;
        }else{
            $importacaoEmbarqueObj->transit_time_chegada = null;
        }
        $importacaoEmbarqueObj->save();

        if(!empty($fields['arquivo_carta_programada'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'carta_programa')->count();
            foreach($fields['arquivo_carta_programada'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_carta_programa_".$indice;
                $name_arquivo_carta_programada = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/carta_programada", $name_arquivo_carta_programada);
            
                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_carta_programada;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "carta_programa";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_proforma'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'proforma')->count();
            foreach($fields['arquivo_proforma'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_proforma_".$indice;
                $name_arquivo_proforma = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_proforma);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_proforma;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "proforma";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_conciliator_invoice'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'conciliator_invoice')->count();
            foreach($fields['arquivo_conciliator_invoice'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_conciliator_invoice_".$indice;
                $name_arquivo_conciliator_invoice = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/conciliator_invoice", $name_arquivo_conciliator_invoice);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_conciliator_invoice;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "conciliator_invoice";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_packing_list'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'packing_list')->count();
            foreach($fields['arquivo_packing_list'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_packing_list_".$indice;
                $name_arquivo_packing_list = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/packing_list", $name_arquivo_packing_list);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_packing_list;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "packing_list";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }
        
        if(!empty($fields['arquivo_bl'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'bl')->count();
            foreach($fields['arquivo_bl'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_bl_".$indice;
                $name_arquivo_bl = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/bl", $name_arquivo_bl);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_bl;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "bl";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_contrato_cambio'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'contrato_cambio')->count();
            foreach($fields['arquivo_contrato_cambio'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_contrato_cambio_".$indice;
                $name_arquivo_contrato_cambio = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/contrato_cambio", $name_arquivo_contrato_cambio);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_contrato_cambio;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "contrato_cambio";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_nf_importacao'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'nf_importacao')->count();
            foreach($fields['arquivo_nf_importacao'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_nf_importacao_".$indice;
                $name_arquivo_nf_importacao = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/nf_importacao", $name_arquivo_nf_importacao);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_nf_importacao;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "nf_importacao";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_exoneracao'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'exoneracao')->count();
            foreach($fields['arquivo_exoneracao'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_exoneracao_".$indice;
                $name_arquivo_exoneracao = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/exoneracao", $name_arquivo_exoneracao);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_exoneracao;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "exoneracao";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_nf_remessa'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'nf_remessa')->count();
            foreach($fields['arquivo_nf_remessa'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_nf_remessa_".$indice;
                $name_arquivo_nf_remessa = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/nf_remessa", $name_arquivo_nf_remessa);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_nf_remessa;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "nf_remessa";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_di'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'di')->count();
            foreach($fields['arquivo_di'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_di_".$indice;
                $name_arquivo_di = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/di", $name_arquivo_di);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_di;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "di";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_ci'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'ci')->count();
            foreach($fields['arquivo_ci'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_ci_".$indice;
                $name_arquivo_ci = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_ci);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_ci;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "ci";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_fechamento_processo'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'fechamento_processo')->count();
            foreach($fields['arquivo_fechamento_processo'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_fechamento_processo_".$indice;
                $name_arquivo_fechamento_processo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_fechamento_processo);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_fechamento_processo;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "fechamento_processo";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_armazenagem'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'armazenagem')->count();
            foreach($fields['arquivo_armazenagem'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_armazenagem_".$indice;
                $name_arquivo_armazenagem = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_armazenagem);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_armazenagem;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "armazenagem";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        if(!empty($fields['arquivo_afrmm'])){
            $indice =  ImportacaoDocumento::where('importacaos_id', $importacaoObj->id)->where('tipo', 'afrmm')->count();
            foreach($fields['arquivo_afrmm'] as $key => $arquivo){
                $name_arquivo = $fornecedor->codigo."_".$proforma->proforma."_afrmm_".$indice;
                $name_arquivo_afrmm = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                $path_file = $arquivo->storeAs($this->path.$importacaoObj->id."/proforma", $name_arquivo_afrmm);

                $importacaoDocumentoObj = new ImportacaoDocumento;
                $importacaoDocumentoObj->importacaos_id = $importacaoObj->id;
                $importacaoDocumentoObj->nome_arquivo = $name_arquivo_afrmm;
                $importacaoDocumentoObj->caminho = $path_file;
                $importacaoDocumentoObj->tipo = "afrmm";
                $importacaoDocumentoObj->created_by = Auth::id();
                $importacaoDocumentoObj->save();

                $indice++;
            }
        }

        $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoCustoObj)){
            $importacaoCustoObj = new ImportacaoCusto;
            $importacaoCustoObj->importacaos_id = $importacaoObj->id;
        }

        $importacaoCustoObj->ii = empty($fields['ii'])? null : parserNumber($fields['ii']);
        $importacaoCustoObj->ipi = empty($fields['ipi'])? null : parserNumber($fields['ipi']);
        $importacaoCustoObj->pis = empty($fields['pis'])? null : parserNumber($fields['pis']);
        $importacaoCustoObj->cofins = empty($fields['cofins'])? null : parserNumber($fields['cofins']);
        $importacaoCustoObj->afrmm = empty($fields['afrmm'])? null : parserNumber($fields['afrmm']);
        $importacaoCustoObj->taxa_siscomex = empty($fields['taxa_siscomex'])? null : parserNumber($fields['taxa_siscomex']);
        $importacaoCustoObj->sda = empty($fields['sda'])? null : parserNumber($fields['sda']);
        $importacaoCustoObj->honorarios = empty($fields['honorarios'])? null : parserNumber($fields['honorarios']);
        $importacaoCustoObj->expediente = empty($fields['expediente'])? null : parserNumber($fields['expediente']);
        $importacaoCustoObj->valor_li = empty($fields['valor_li'])? null : parserNumber($fields['valor_li']);
        $importacaoCustoObj->agencia_maritima = empty($fields['agencia_maritima'])? null : parserNumber($fields['agencia_maritima']);
        $importacaoCustoObj->armazem = empty($fields['armazem'])? null : parserNumber($fields['armazem']);
        $importacaoCustoObj->laudo = empty($fields['laudo'])? null : parserNumber($fields['laudo']);
        $importacaoCustoObj->outras_despesas = empty($fields['outras_despesas'])? null : parserNumber($fields['outras_despesas']);
        $importacaoCustoObj->icms_saida = empty($fields['icms_saida'])? null : parserNumber($fields['icms_saida']);
        $importacaoCustoObj->seguro = empty($fields['seguro'])? null : parserNumber($fields['seguro']);
        $importacaoCustoObj->transporte_rodoviario = empty($fields['transporte_rodoviario'])? null : parserNumber($fields['transporte_rodoviario']);
        $importacaoCustoObj->save();

        $importacaoFinanceiroObj = ImportacaoFinanceiro::where('importacaos_id', $importacaoObj->id)->first();
        if(empty($importacaoFinanceiroObj)){
            $importacaoFinanceiroObj = new ImportacaoFinanceiro;
            $importacaoFinanceiroObj->importacaos_id = $importacaoObj->id;
        }
        if(!empty($fields['data_embarque_financeiro'])){
            $data_embarque_financeiro = Carbon::createFromFormat('d/m/Y', $fields['data_embarque_financeiro'])->setTime(0,0,0);
            $importacaoFinanceiroObj->data_embarque = $data_embarque_financeiro;
        }else{
            $importacaoFinanceiroObj->data_embarque = null;
        }
        
        $importacaoFinanceiroObj->debito_credito = empty($fields['valor_fob_devido'])? null : parserNumber($fields['valor_fob_devido']);
        $importacaoFinanceiroObj->save();

        $importacaoFollowUpObj = ImportacaoFollowUp::select()->where('importacaos_id', $id)->first();

        $aprovacao_quality_sample_realizado = empty($fields['aprovacao_quality_sample_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_realizado'])->setTime(0,0,0);
        $aprovacao_laboratorio_realizado = empty($fields['aprovacao_laboratorio_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_laboratorio_realizado'])->setTime(0,0,0);
        $aprovacao_amostra_embarque_enviado = empty($fields['aprovacao_amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_enviado'])->setTime(0,0,0);

        if($importacaoFollowUpObj->quality_sample_data_aprovacao != $aprovacao_quality_sample_realizado){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'quality_sample';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_quality_sample'])? null : $fields['aprovacao_quality_sample'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        if($importacaoFollowUpObj->laboratorio_data_aprovacao != $aprovacao_laboratorio_realizado){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'laboratorio';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_laboratorio'])? null : $fields['aprovacao_laboratorio'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        if($importacaoFollowUpObj->amostra_embarque_data_aprovacao != $aprovacao_amostra_embarque_enviado){
            $importacaoFollowUpHistoricoAprovacaoObj = new ImportacaoFollowUpHistoricoAprovacao;
            $importacaoFollowUpHistoricoAprovacaoObj->importacao_follow_up_id = $importacaoFollowUpObj->id;
            $importacaoFollowUpHistoricoAprovacaoObj->tipo = 'amostra_embarque';
            $importacaoFollowUpHistoricoAprovacaoObj->aprovado = empty($fields['aprovacao_amostra_embarque'])? null : $fields['aprovacao_amostra_embarque'];;
            $importacaoFollowUpHistoricoAprovacaoObj->data = $aprovacao_quality_sample_realizado;
            $importacaoFollowUpHistoricoAprovacaoObj->created_by = Auth::id();
            $importacaoFollowUpHistoricoAprovacaoObj->save();
        }

        $importacaoFollowUpObj->tipo_cor = empty($fields['envio_das_cores'])? null : $fields['envio_das_cores'];
        $importacaoFollowUpObj->envio_cor_data_previsao = empty($fields['envio_das_cores_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_envio = empty($fields['envio_das_cores_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_recebido = empty($fields['envio_das_cores_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->envio_cor_data_revisao = empty($fields['envio_das_cores_revisao'])? null : Carbon::createFromFormat('d/m/Y', $fields['envio_das_cores_revisao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_aprovacao = empty($fields['aprovacao_quality_sample'])? null : $fields['aprovacao_quality_sample'];
        $importacaoFollowUpObj->quality_sample_data_previsao_envio = empty($fields['quality_sample_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_envio = empty($fields['quality_sample_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_recebido = empty($fields['quality_sample_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['quality_sample_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_previsao_aprovacao = empty($fields['aprovacao_quality_sample_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_data_aprovacao = empty($fields['aprovacao_quality_sample_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_quality_sample_realizado'])->setTime(0,0,0);
        $importacaoFollowUpObj->quality_sample_transportadora = empty($fields['quality_sample_transportadora'])? null : $fields['quality_sample_transportadora'];
        $importacaoFollowUpObj->quality_sample_awb = empty($fields['quality_sample_awb'])? null : $fields['quality_sample_awb'];
        $importacaoFollowUpObj->laboratorio_aprovacao = empty($fields['aprovacao_laboratorio'])? null : $fields['aprovacao_laboratorio'];
        $importacaoFollowUpObj->laboratorio_data_previsao_envio = empty($fields['laboratorio_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_envio = empty($fields['laboratorio_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_recebido = empty($fields['laboratorio_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['laboratorio_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->laboratorio_data_aprovacao = empty($fields['aprovacao_laboratorio_realizado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_laboratorio_realizado'])->setTime(0,0,0);
        $importacaoFollowUpObj->tempo_producao_previsao_termino = empty($fields['tempo_producao_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['tempo_producao_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->tempo_producao_termino = empty($fields['tempo_producao'])? null : Carbon::createFromFormat('d/m/Y', $fields['tempo_producao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_aprovacao = empty($fields['aprovacao_amostra_embarque'])? null : $fields['aprovacao_amostra_embarque'];
        $importacaoFollowUpObj->amostra_embarque_data_previsao_envio = empty($fields['amostra_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_envio = empty($fields['amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_recebido = empty($fields['amostra_embarque_recebido'])? null : Carbon::createFromFormat('d/m/Y', $fields['amostra_embarque_recebido'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_previsao_aprovacao = empty($fields['aprovacao_amostra_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_data_aprovacao = empty($fields['aprovacao_amostra_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['aprovacao_amostra_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->amostra_embarque_transportadora = empty($fields['amostra_embarque_transportadora'])? null : $fields['amostra_embarque_transportadora'];
        $importacaoFollowUpObj->amostra_embarque_awb = empty($fields['amostra_embarque_awb'])? null : $fields['amostra_embarque_awb'];
        $importacaoFollowUpObj->autorizacao_embarque_data_previsao_envio = empty($fields['autorizacao_embarque_previsao'])? null : Carbon::createFromFormat('d/m/Y', $fields['autorizacao_embarque_previsao'])->setTime(0,0,0);
        $importacaoFollowUpObj->autorizacao_embarque_data_envio = empty($fields['autorizacao_embarque_enviado'])? null : Carbon::createFromFormat('d/m/Y', $fields['autorizacao_embarque_enviado'])->setTime(0,0,0);
        $importacaoFollowUpObj->updated_by = Auth::id();
        $importacaoFollowUpObj->save();

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $empresas = returnEmpresasNasajonView();

        $importacaoObj = Importacao::find($id);

        $dados = [
            'id' => encrypt($id),
            'fornecedor' => $importacaoObj->fornecedor->nome.' - '.$importacaoObj->fornecedor->cnpj_cpf,
            'proforma' => $importacaoObj->numero_proforma,
            'pcmn' => $importacaoObj->pedido_compras,
        ];

        return view('programs.importacao.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
         
        $importacaoObj = Importacao::find($id);

        $importacaoObj->deleted_by = Auth::id();
        $importacaoObj->save();
        $importacaoObj->delete();


        $importacaoItemObj = ImportacaoItem::where('importacaos_id',$id);
        $importacaoItemObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('estabelecimento_filtro', 'fornecedor_filtro', 'proforma_filtro', 'pedido_filtro', 'data_filtro_inicial', 'data_filtro_final');

        $empresas = returnEmpresasNasajonView();

        $query = Importacao::select();
        $query->with(['fornecedor' => function($query) use($fields){
            if(!empty($fields['fornecedor_filtro'])){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }
        }]);
        if(!empty($fields['estabelecimento_filtro'])){
            $query->where('estabelecimento_codigo', str_pad($fields["estabelecimento_filtro"], 2, '0', STR_PAD_LEFT));
        }
        if(!empty($fields['proforma_filtro'])){
            $query->where('numero_proforma', 'ilike', '%'.$fields["proforma_filtro"].'%');
        }
        if(!empty($fields['pedido_filtro'])){
            $query->where('pedido_compras', $fields["pedido_filtro"]);
        }
        if(!empty($fields['data_filtro_inicial'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_filtro_inicial']);;
            $query->where('data_proforma', '>=', $data_inicial);
        }

        if(!empty($fields['data_filtro_final'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_filtro_final']);;
            $query->where('data_proforma', '<=', $data_final);
        }

        $result = $query->get();

        $retorno = [];
        foreach($result as $importacao){
            if(!empty($importacao->fornecedor)){
                $retorno[] = [
                    'id' => encrypt($importacao->id),
                    'estabelecimento' => $empresas[intval($importacao->estabelecimento_codigo)],
                    'fornecedor' => $importacao->fornecedor->nome.' - '.$importacao->fornecedor->cnpj_cpf,
                    'proforma' => $importacao->numero_proforma,
                    'pcmn' => $importacao->pedido_compras,
                    'referencia' => $importacao->referencia,
                    'data' => parserData($importacao->data_proforma),
                ];
            }
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno
        ];

        return response()->json($response);
    }

    public function modalBuscarProforma(Request $request) {
        $fields = $request->only('fornecedor');

        $fornecedor = !isset($fields['fornecedor'])? '' : $fields['fornecedor'];
        
        $status = [];
        $status['aberto'] = 'Aberto';
        $status['aguardando_documento'] = 'Aguardando Documento';
        $status['parcialmente_liquidado'] = 'Parcialmente Liquidado';
        $status['liquidado'] = 'Liquidado';
        $status['cancelado'] = 'Cancelado';

        return view('programs.importacao.modal.buscar_proforma')->with(['fornecedor' => $fornecedor, 'status' => $status]);
    }

    public function filtroBuscarProforma(Request $request){
        $fields = $request->only('status', 'fornecedor_dialog', 'pedido', 'data_dialog_inicial', 'data_dialog_final');

        $empresas = returnEmpresasNasajonView();

        $proformas = [];

        $query = ComprasNasajon::select('estabelecimento', 'fornecedor_cnpj', 'fornecedor_nome', 'numero_pedido', 'proforma', 'data_compra', 'previsao_entrega', 'data_entrega', 'situacao');
        $query->whereNotNull('proforma');
        $query->where('estabelecimento', '03');
        $query->where('situacao', 'NOT ILIKE', 'cancelado');

        if(!empty($fields['status'])){
            $status = str_replace("_", " ", $fields['status']);
            $query->where('situacao', 'ILIKE', trim($fields['status']));
        }

        if(!empty($fields['fornecedor_dialog'])){
            $query->where(DB::raw('TRIM(CONCAT(TRIM(fornecedor_nome),\' - \', fornecedor_cnpj))'), 'ILIKE', '%'.trim($fields['fornecedor_dialog']).'%');
        }

        if(!empty($fields['pedido'])){
            $query->where('numero_pedido', $fields["pedido"]);
        }

        if(!empty($fields['data_dialog_inicial'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_dialog_inicial']);;
            $query->where('data_compra', '>=', $data_inicial);
        }

        if(!empty($fields['data_dialog_final'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_dialog_final']);;
            $query->where('data_compra', '<=', $data_final);
        }

        $query->distinct();
        $result = $query->get();

        foreach($result as $value){
            $proformas [] = [
                'estabelecimento' => $empresas[intval($value->estabelecimento)],
                'fornecedor' => $value->fornecedor_nome." - ".$value->fornecedor_cnpj,
                'pedido' => $value->numero_pedido,
                'proforma' => $value->proforma,
                'data_compra' => parserData($value->data_compra),
                'previsão_entrega' => parserData($value->previsao_entrega),
                'data_entrega' => empty($value->data_entrega)? '' : parserData($value->data_entrega),
                'status' => $value->situacao,
            ];
        }        

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $proformas
        ];

        return response()->json($response);
    }

    public function autocompleteProforma(Request $request){
        $fields = $request->only('term', 'fornecedor');
        $return = [];
        $query = ComprasNasajon::select('proforma')
            ->where('estabelecimento', '03')
            ->whereNotNull('proforma');
        if(!empty($fields['fornecedor'])){
            $query->where(DB::raw('TRIM(CONCAT(TRIM(fornecedor_nome),\' - \', fornecedor_cnpj))'), 'ILIKE', trim($fields['fornecedor']));
        }
        $query->limit("15")
            ->distinct()
            ->orderBy('proforma', "ASC")
            ->where('proforma', 'ILIKE', '%'.$fields['term'].'%');
        $result = $query->get()->toArray();

        foreach ($result as $value){
            $value = (array) $value;
            $return[] = [
                'label' => trim($value['proforma']),
                'value' => $value['proforma']
            ];
        }

        return response()->json($return);
    }

    public function retornoDadosProforma(Request $request){
        $fields = $request->only('numero_proforma', 'fornecedor', 'pedido_compras');

        $query = ComprasNasajon::select('id_nota','estabelecimento', 'fornecedor_cnpj', 'fornecedor_nome', 'numero_pedido', 'proforma', 'data_compra', 'previsao_entrega', 'data_entrega', 'situacao');
        if(!empty($fields['numero_proforma'])){
            $query->where('proforma', 'ilike', $fields['numero_proforma']);
        }else{
            $query->where('numero_pedido', 'ilike', $fields['pedido_compras']);
        }
        
        $query->where('estabelecimento', '03');
        $query->where('situacao', '!=', 'Cancelado');
        if(!empty($fields['fornecedor'])){
            $query->where(DB::raw('TRIM(CONCAT(TRIM(fornecedor_nome),\' - \', fornecedor_cnpj))'), 'ILIKE', trim($fields['fornecedor']));
        }
        $proforma = $query->first();
        
        if(empty($proforma)){
            return response()->json([
                'status' => 'error',
                'message' => 'Proforma não encontrado',
                'error' => ["numero_proforma" => "Proforma não encontrado"],
                'response' => []
            ],422);
        }

        $query = ComprasNasajon::select('cod_produto', 'descricao_produto', 'quantidade', 'preco_compra_unitario', 'preco_compra' );
        $query->with(['produto', 'produto.produtoGrupo', 'produtoNasajon']);
        $query->where('proforma', 'ilike', $proforma->proforma);
        $query->where('situacao', '!=', 'Cancelado');
        $query->where('estabelecimento', '03');
        if(!empty($fields['fornecedor'])){
            $query->where(DB::raw('TRIM(CONCAT(TRIM(fornecedor_nome),\' - \', fornecedor_cnpj))'), 'ILIKE', trim($fields['fornecedor']));
        }
        $produtos = $query->get();

        $tabela_produtos = [];
        $index = 0;

        $total = [
            'quantidade' => 0,
            'preco_fob' => 0,
        ];
        foreach($produtos as $produto){
            $index++;
            if(isset($produto->produto->foto) && Storage::exists('public/produto_fotos/' . $produto->produto->foto->filename) && Storage::exists('public/produto_fotos/' . $produto->produto->foto->thumb_filename)){
                $foto= "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $produto->produto->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $produto->produto->foto->filename) . "\" class=\"btn-foto-estoque thumb ml-2 mt-1\"></a>"; 
            }
            else{
                $foto = "";
            }

            $tabela_produtos[] = [
                'item' => $index,
                'codigo' => $produto->cod_produto,
                'produto' => empty($produto->produto)? '' : $produto->produto->descricao,
                'composicao' => $produto->produtoNasajon->composicao,
                'gramatura_gm2' => empty($produto->produto->produtoGrupo)? '' : $produto->produto->produtoGrupo->gramatura_gm2,
                'largura' => empty($produto->produto->produtoGrupo)? '' : $produto->produto->produtoGrupo->largura,
                'gramatura_gml' => '',
                'rendimento' => '',
                'instrucao_lavagem' => '',
                'quantidade' => parserValor4CasasDecimais($produto->quantidade),
                'preco_unitario' => parserValor4CasasDecimais($produto->preco_compra_unitario),
                'preco_total' => parserValor4CasasDecimais($produto->preco_compra),
                'foto' => $foto
            ];

            $total['quantidade'] += $produto->quantidade;
            $total['preco_fob'] += $produto->preco_compra;
        }

        $total['quantidade'] = empty($total['quantidade'])? '': parserValor4CasasDecimais($total['quantidade']);
        $total['preco_fob'] = empty($total['preco_fob'])? '': parserValor4CasasDecimais($total['preco_fob']);

        $retorno = [
            'id_nota' => encrypt($proforma->id_nota),
            'estabelecimento' => $proforma->estabelecimento, 
            'fornecedor_cnpj' => empty($proforma->fornecedor_cnpj)? '' : $proforma->fornecedor_cnpj, 
            'fornecedor_nome' => trim($proforma->fornecedor_nome), 
            'numero_pedido' => $proforma->numero_pedido, 
            'proforma' => $proforma->proforma, 
            'data_compra' => parserData($proforma->data_compra), 
            'previsao_entrega' => parserData($proforma->previsao_entrega), 
            'data_entrega' => empty($proforma->data_entrega)? '' : parserData($proforma->data_entrega), 
            'situacao' => $proforma->situacao,
            'tabela_produtos' => $tabela_produtos,
            'total' => $total
        ];
        
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno
        ];

        return response()->json($response);
    }

    public function salvamentoProforma(ImportacaoRequest $request){
        $fields = $request->only('fornecedor', 'numero_proforma');

        $custo_previsto = [
            'total_cambio_previsto' => '',
            'ii_previsto' => '',
            'ipi_previsto' => '',
            'pis_previsto' => '',
            'cofins_previsto' => '',
            'afrmm_previsto' => '',
            'taxa_siscomex_previsto' => '',
            'sda_previsto' => '',
            'honorarios_previsto' => '',
            'expediente_previsto' => '',
            'valor_li_previsto' => '',
            'agencia_maritima_previsto' => '',
            'laudo_previsto' => '',
            'icms_saida_previsto' => '',
            'seguro_previsto' => '',
            'armazenagem_previsto' => '',
            'outras_despesas_previsto' => '',
            'total_previsto' => '',
        ];
        
        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor = $fornecedor->first();

        $proforma = ComprasNasajon::select();
        $proforma->where('proforma', 'ILIKE', ($fields['numero_proforma']));
        $proforma->where('estabelecimento', '03');
        $proforma = $proforma->first();

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $importacaoObj = new Importacao;
        $importacaoObj->estabelecimento_codigo = $proforma->estabelecimento;
        $importacaoObj->fornecedor_codigo = $fornecedor->codigo;
        $importacaoObj->numero_proforma = $proforma->proforma;
        $importacaoObj->pedido_compras = $proforma->numero_pedido;
        $importacaoObj->data_proforma = $proforma->data_compra;
        $importacaoObj->data_previsao_recebimento = $proforma->previsao_entrega;
        $importacaoObj->created_by = Auth::id();
        $importacaoObj->save();

        if(!empty($importacaoValorPadraoObj)){
            $importacaoObj->importacao_valor_padraos_id = $importacaoValorPadraoObj->id;
       
            $importacaoCustoObj = new ImportacaoCusto;
            $importacaoCustoObj->importacaos_id = $importacaoObj->id;
            $importacaoCustoObj->pis = $importacaoValorPadraoObj->pis;
            $importacaoCustoObj->cofins = $importacaoValorPadraoObj->cofins;
            $importacaoCustoObj->taxa_siscomex = $importacaoValorPadraoObj->taxa_siscomex;
            $importacaoCustoObj->sda = $importacaoValorPadraoObj->sda;
            $importacaoCustoObj->honorarios = $importacaoValorPadraoObj->honorarios;
            $importacaoCustoObj->expediente = $importacaoValorPadraoObj->expediente;
            $importacaoCustoObj->armazem = $importacaoValorPadraoObj->armazenagem;
            $importacaoCustoObj->laudo = $importacaoValorPadraoObj->laudo;
            $importacaoCustoObj->transporte_rodoviario = $importacaoValorPadraoObj->frete_rodoviario;
            $importacaoCustoObj->save();


            $query = ComprasNasajon::select(DB::raw("SUM(preco_compra) as total"));
            $query->with(['produto', 'produto.produtoGrupo', 'produtoNasajon']);
            $query->where('proforma', 'ilike', $proforma->proforma);
            $query->where('estabelecimento', '03');
            $result = $query->first();

            $preco_fob = $result->total;
            $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $preco_fob * $importacaoObj->valorPadrao->dolar_referencia;
            $total_custo = $importacaoValorPadraoObj->pis + $importacaoValorPadraoObj->cofins + $importacaoValorPadraoObj->taxa_siscomex + $importacaoValorPadraoObj->sda + $importacaoValorPadraoObj->honorarios + $importacaoValorPadraoObj->expediente + $importacaoValorPadraoObj->armazenagem + $importacaoValorPadraoObj->laudo + $total_cambio_previsto;


            $ii_previsto = $total_cambio_previsto * 26 / 100;
            $pis_previsto = $total_cambio_previsto * $importacaoValorPadraoObj->pis / 100;
            $cofins_previsto = $total_cambio_previsto * $importacaoValorPadraoObj->cofins / 100;

            $custo_previsto = [
                'total_cambio_previsto' => empty($total_cambio_previsto)? '' : parserValor($total_cambio_previsto),
                'ii_previsto' => empty($ii_previsto)? '' : parserValor($ii_previsto),
                'ipi_previsto' => empty($importacaoCustoObj->ipi)? '' : parserValor($importacaoCustoObj->ipi),
                'pis_previsto' => empty($pis_previsto)? '' : parserValor($pis_previsto),
                'cofins_previsto' => empty($cofins_previsto)? '' : parserValor($cofins_previsto),
                'afrmm_previsto' => empty($importacaoCustoObj->afrmm)? '' : parserValor($importacaoCustoObj->afrmm),
                'taxa_siscomex_previsto' => empty($importacaoCustoObj->taxa_siscomex)? '' : parserValor($importacaoCustoObj->taxa_siscomex),
                'sda_previsto' => empty($importacaoCustoObj->sda)? '' : parserValor($importacaoCustoObj->sda),
                'honorarios_previsto' => empty($importacaoCustoObj->honorarios)? '' : parserValor($importacaoCustoObj->honorarios),
                'expediente_previsto' => empty($importacaoCustoObj->expediente)? '' : parserValor($importacaoCustoObj->expediente),
                'valor_li_previsto' => empty($importacaoCustoObj->valor_li)? '' : parserValor($importacaoCustoObj->valor_li),
                'agencia_maritima_previsto' => empty($importacaoCustoObj->agencia_maritima)? '' : parserValor($importacaoCustoObj->agencia_maritima),
                'laudo_previsto' => empty($importacaoCustoObj->laudo)? '' : parserValor($importacaoCustoObj->laudo),
                'icms_saida_previsto' => empty($importacaoCustoObj->icms_saida)? '' : parserValor($importacaoCustoObj->icms_saida),
                'seguro_previsto' => empty($importacaoCustoObj->seguro)? '' : parserValor($importacaoCustoObj->seguro),
                'armazenagem_previsto' => empty($importacaoCustoObj->armazenagem)? '' : parserValor($importacaoCustoObj->armazenagem),
                'outras_despesas_previsto' => empty($importacaoCustoObj->outras_despesas)? '' : parserValor($importacaoCustoObj->outras_despesas),
                'total_previsto' => empty($total_custo)? '' : parserValor($total_custo),
                'transporte_rodoviario_previsto' => empty($importacaoCustoObj->transporte_rodoviario)? '': parserValor($importacaoCustoObj->transporte_rodoviario),
            ];
        }

        foreach($importacaoObj->pedidoComprasItens as $item){
            if($item->numero_pedido == $importacaoObj->pedido_compras){
                $importacaoItemObj = new ImportacaoItem;
                $importacaoItemObj->importacaos_id = $importacaoObj->id;
                $importacaoItemObj->produto_codigo = $item->cod_produto;
                $importacaoItemObj->valor_contabil_unitario = 0;
                $importacaoItemObj->estabelecimento_codigo = $importacaoObj->estabelecimento_codigo;
                $importacaoItemObj->numero_proforma = $importacaoObj->numero_proforma;
                $importacaoItemObj->pedido_compras = $importacaoObj->pedido_compras;
                $importacaoItemObj->nota_uuid_nasajon = $item->id_nota;
                $importacaoItemObj->save();
            }
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                    'id' => encrypt($importacaoObj->id),
                    'custo_previsto' => $custo_previsto
                ]
        ];

        return response()->json($response);
    }

    public function adicionarOutrasDepesas(Request $request){
        $fields = $request->only('id', 'descricao_outras_despesas', 'outras_despesas_valor');
        
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoCustoOutraDespesaObj = new ImportacaoCustoOutraDespesa;
        $importacaoCustoOutraDespesaObj->importacaos_id = $id;
        $importacaoCustoOutraDespesaObj->descricao = $fields['descricao_outras_despesas'];
        $importacaoCustoOutraDespesaObj->valor = parserNumber($fields['outras_despesas_valor']);
        $importacaoCustoOutraDespesaObj->save();

        $query = ImportacaoCustoOutraDespesa::select();
        $query->where('importacaos_id', $id);
        $result = $query->get();
        $total = 0;
        $outras_despesas = [];
        foreach($result as $outra_depesa){
            $outras_despesas[] = [
                'id' => encrypt($outra_depesa->id),
                'descricao' => $outra_depesa->descricao,
                'valor' => parserValor($outra_depesa->valor),
            ];

            $total += $outra_depesa->valor;
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'outras_despesas' => $outras_despesas,
                'total' => $total,
            ]
        ];

        return response()->json($response);
    }

    public function modalDetalhes(Request $request){
        $id = $request->only(['id'])['id'];
       
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $cotacaoDoDiaObj = Cotacoes::wherehas('moeda', function($query){
            $query->where('codigo','220');
        })
        ->orderBy('data', 'desc')->first();
        $importacaoDadoComplemetarFollowUpControllerObj = new ImportacaoDadoComplemetarFollowUpController;
        
        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $importacaoObj = Importacao::find($id);
   
        if(!empty($importacaoValorPadraoObj)){
            if(empty($importacaoObj->importacao_valor_padraos_id)){
                $importacaoObj->importacao_valor_padraos_id = $importacaoValorPadraoObj->id;
                $importacaoObj->save();

                $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $importacaoObj->id)->first();
                if(empty($importacaoCustoObj)){
                    $importacaoCustoObj = new ImportacaoCusto;
                    $importacaoCustoObj->importacaos_id = $importacaoObj->id;
                }
                
                if(empty($importacaoCustoObj->pis)){
                    $importacaoCustoObj->pis = $importacaoValorPadraoObj->pis;
                }
                if(empty($importacaoCustoObj->cofins)){
                    $importacaoCustoObj->cofins = $importacaoValorPadraoObj->cofins;
                }
                if(empty($importacaoCustoObj->taxa_siscomex)){
                    $importacaoCustoObj->taxa_siscomex = $importacaoValorPadraoObj->taxa_siscomex;
                }
                if(empty($importacaoCustoObj->sda)){
                    $importacaoCustoObj->sda = $importacaoValorPadraoObj->sda;
                }
                if(empty($importacaoCustoObj->honorarios)){
                    $importacaoCustoObj->honorarios = $importacaoValorPadraoObj->honorarios;
                }
                if(empty($importacaoCustoObj->expediente)){
                    $importacaoCustoObj->expediente = $importacaoValorPadraoObj->expediente;
                }
                if(empty($importacaoCustoObj->armazem)){
                    $importacaoCustoObj->armazem = $importacaoValorPadraoObj->armazenagem;
                }
                if(empty($importacaoCustoObj->laudo)){
                    $importacaoCustoObj->laudo = $importacaoValorPadraoObj->laudo;
                }

                $importacaoCustoObj->save();
            }
        }

        $itens = [];
        $index = 0;
        $total = [
            'quantidade' => 0,
            'quantidade_realizada' => 0,
            'preco_fob' => 0,
            'preco_contabil' => 0,
            'diferenca_preco' => 0,
        ];


        if(count($importacaoObj->itens) == 0){
            foreach($importacaoObj->pedidoComprasItens as $item){
                if($item->numero_pedido == $importacaoObj->pedido_compras){
                    $importacaoItemObj = new ImportacaoItem;
                    $importacaoItemObj->importacaos_id = $importacaoObj->id;
                    $importacaoItemObj->produto_codigo = $item->cod_produto;
                    $importacaoItemObj->valor_contabil_unitario = 0;
                    $importacaoItemObj->estabelecimento_codigo = $importacaoObj->estabelecimento_codigo;
                    $importacaoItemObj->numero_proforma = $importacaoObj->numero_proforma;
                    $importacaoItemObj->pedido_compras = $importacaoObj->pedido_compras;
                    $importacaoItemObj->nota_uuid_nasajon = $item->id_nota;
                    $importacaoItemObj->save();
                }
            }
        }

        foreach($importacaoObj->pedidoComprasItens as $item){ 
            
      
            $index++;
            if(isset($item->produto->foto) && Storage::exists('public/produto_fotos/' . $item->produto->foto->filename) && Storage::exists('public/produto_fotos/' . $item->produto->foto->thumb_filename)){
                $foto= "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $item->produto->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $item->produto->foto->filename) . "\" class=\"btn-foto-estoque thumb ml-2 mt-1\"></a>"; 
            }
            else{
                $foto = "";
            }


            $itens[] = [
                'item' => $index,
                'codigo' => $item->cod_produto,
                'produto' => empty($item->produto)? '' : $item->produto->descricao,
                'composicao' => $item->produtoNasajon->composicao,
                'gramatura_gm2' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->gramatura_gm2,
                'largura' => empty($item->produto->produtoGrupo)? '' : $item->produto->produtoGrupo->largura,
                'gramatura_gml' => '',
                'rendimento' => '',
                'instrucao_lavagem' => '',
                'quantidade' => parserValor4CasasDecimais($item->quantidade),
                'quantidade_realizada' => $item->situacao_item == 'Cancelado'? '' : parserValor4CasasDecimais($item->quantidade - $item->quantidade_restante),
                'preco_unitario' => parserValor4CasasDecimais($item->preco_compra_unitario),
                'preco_total' => parserValor4CasasDecimais($item->quantidade * $item->preco_compra_unitario),
                'foto' => $foto,
                'status' => $item->situacao_item,
                'valor_contabil_unitario' => empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario),
                'valor_contabil_total' =>  empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? '' : parserValor4CasasDecimais($item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade),
            ];

            $total['quantidade'] += $item->quantidade;
            $total['quantidade_realizada'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade - $item->quantidade_restante;
            $total['preco_fob'] += $item->situacao_item == 'Cancelado'? 0 : $item->quantidade * $item->preco_compra_unitario;
            if(empty($item->produtoDetalhesImportacao->valor_contabil_unitario)){
                $total['preco_contabil'] = 0;
            }else{
                $total['preco_contabil'] += $item->situacao_item == 'Cancelado'? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
            }
        
        }

        $aprovacao_amostra_embarque = '';
        if(!empty($importacaoObj->aprovacao_amostra_embarque)){
            $aprovacao_amostra_embarque = $importacaoObj->aprovacao_amostra_embarque? 0 : 1;
        }

        $aprovado_embarque_produto_codigo = '';
        $aprovado_embarque_produto = '';
        if(!empty($importacaoObj->aprovadoEmbarqueProdutoDetalhes)){
            $aprovado_embarque_produto_codigo = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->codigo_produto;
            $aprovado_embarque_produto = $importacaoObj->aprovadoEmbarqueProdutoDetalhes->descricao;
        }

        $arquivo_carta_programada = '';
        if(!empty($importacaoObj->arquivo_carta_programada)){
            $arquivo_carta_programada = Storage::url($importacaoObj->arquivo_carta_programada);
        }
        
        $porto_origem = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->porto_origem;
        $porto_destino = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->porto_destino;
        $agente_compra = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->agente_compra;
        $armador = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->armador;
        $etd_booking = empty($importacaoObj->embarqueDetalhes)? false : $importacaoObj->embarqueDetalhes->etd_booking;
        $eta_booking = empty($importacaoObj->embarqueDetalhes)? false : $importacaoObj->embarqueDetalhes->eta_booking;
        $numero_bl = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->numero_bl;
        $transit_time_chegada = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->transit_time_chegada;
        $transit_time_saida = empty($importacaoObj->embarqueDetalhes)? '' : $importacaoObj->embarqueDetalhes->transit_time_saida;

        $arquivos = [];
        foreach($importacaoObj->documentos as $documento){
            $nome_completo = explode(".", $documento->nome_arquivo);
            $arquivos['arquivo_'.$documento->tipo][] = [
                'caminho' => Storage::url($documento->caminho),
                'nome' => $documento->tipo,
                'nome_resumido' => strlen($documento->tipo) > 10? substr($documento->tipo, 0, 10)."..." : $documento->tipo,
                'extensao' => strtolower($nome_completo[1])
            ];
        }

        $ii = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->ii);
        $ipi = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->ipi);
        $pis = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->pis);
        $cofins = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->cofins);
        $afrmm = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->afrmm);
        $taxa_siscomex = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->taxa_siscomex);
        $sda = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->sda);
        $honorarios = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->honorarios);
        $expediente = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->expediente);
        $valor_li = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->valor_li);
        $agencia_maritima = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->agencia_maritima);
        $armazem = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->armazem);
        $laudo = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->laudo);
        $outras_despesas = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->outras_despesas);
        $icms_saida = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->icms_saida);
        $seguro = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->seguro);
        $transporte_rodoviario = empty($importacaoObj->custos)? '' : parserValor($importacaoObj->custos->transporte_rodoviario);

        $data_embarque = empty($importacaoObj->financeiro)? '' : $importacaoObj->financeiro->data_embarque;
        $debito_credito = empty($importacaoObj->financeiro)? '' : $importacaoObj->financeiro->debito_credito;

        if($importacaoObj->pago){
            $valor_fob_pago = $total['preco_fob'];
        }else if(!empty($importacaoObj->financeiro)){
            if(!empty($importacaoObj->financeiro->lancamentos)){
                $valor_fob_pago = empty($importacaoObj->financeiro->valorCambioDolarTotal->total)? 0 : $importacaoObj->financeiro->valorCambioDolarTotal->total;
            }else{
                $valor_fob_pago = 0;
            }
        }else{
            $valor_fob_pago = 0;
        }

        $outras_despesas = [];
        $outras_despesas_total = 0;
        foreach($importacaoObj->custosOutraDespesa as $custo_outra_depesa){
            $outras_despesas[] = [
                'id' => encrypt($custo_outra_depesa->id),
                'descricao' => $custo_outra_depesa->descricao,
                'valor' => parserValor($custo_outra_depesa->valor),
            ];

            $outras_despesas_total += $custo_outra_depesa->valor;
        }

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $total['preco_fob'] * $importacaoObj->valorPadrao->dolar_referencia;
        $total_cambio_realizado = empty($importacaoObj->financeiro->valorCambioTotal->total)? '' : parserValor($importacaoObj->financeiro->valorCambioTotal->total);

        $ii_previsto = $total_cambio_previsto * 26 / 100;
        $pis_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->pis / 100;
        $cofins_previsto = $total_cambio_previsto * $importacaoObj->valorPadrao->cofins / 100;
        $taxa_siscomex_previsto = empty($importacaoObj->valorPadrao->taxa_siscomex)? 0 : $importacaoObj->valorPadrao->taxa_siscomex;
        $agencia_maritima_previsto = empty($importacaoValorPadraoObj->agencia_maritima)? 0 : $importacaoValorPadraoObj->agencia_maritima;
        $afrmm_previsto = $agencia_maritima_previsto * 0.25;

        $icms_saida_previsto = $total_cambio_previsto + $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $agencia_maritima_previsto;
        $icms_saida_previsto = (($icms_saida_previsto / 0.88 ) + (($icms_saida_previsto / 0.88 )* 0.30)) * (4/100);
        
        $icms_a_pagar_previsto = $total_cambio_previsto + $ii_previsto + $pis_previsto + $cofins_previsto + $taxa_siscomex_previsto + $afrmm_previsto + $agencia_maritima_previsto;
        $icms_a_pagar_previsto = (($icms_saida_previsto / 0.88 ) + (($icms_saida_previsto / 0.88 )* 0.30)) * 0.006;

        $icms_a_pagar = parserNumber($total_cambio_realizado) + (($total['preco_fob'] - $valor_fob_pago) * $importacaoValorPadraoObj->dolar_referencia) + parserNumber($ii) + parserNumber($pis) + parserNumber($cofins) + parserNumber($taxa_siscomex) + parserNumber($afrmm) + parserNumber($agencia_maritima);
        $icms_a_pagar = (($icms_a_pagar / 0.88 ) + (($icms_a_pagar / 0.88 )* 0.30)) * 0.006;

        $custo_previsto = [
            'total_cambio_previsto' => empty($total_cambio_previsto)? '' : parserValor($total_cambio_previsto),
            'ii_previsto' => empty($ii_previsto)? '' : parserValor($ii_previsto),
            'ipi_previsto' => empty($importacaoObj->valorPadrao->ipi)? '' : parserValor($importacaoObj->valorPadrao->ipi),
            'pis_previsto' => empty($pis_previsto)? '' : parserValor($pis_previsto),
            'cofins_previsto' => empty($cofins_previsto)? '' : parserValor($cofins_previsto),
            'afrmm_previsto' => empty($afrmm_previsto)? '' : parserValor($afrmm_previsto),
            'taxa_siscomex_previsto' => empty($importacaoObj->valorPadrao->taxa_siscomex)? '' : parserValor($importacaoObj->valorPadrao->taxa_siscomex),
            'sda_previsto' => empty($importacaoObj->valorPadrao->sda)? '' : parserValor($importacaoObj->valorPadrao->sda),
            'honorarios_previsto' => empty($importacaoObj->valorPadrao->honorarios)? '' : parserValor($importacaoObj->valorPadrao->honorarios),
            'expediente_previsto' => empty($importacaoObj->valorPadrao->expediente)? '' : parserValor($importacaoObj->valorPadrao->expediente),
            'valor_li_previsto' => empty($importacaoObj->valorPadrao->valor_li)? '' : parserValor($importacaoObj->valorPadrao->valor_li),
            'agencia_maritima_previsto' => empty($importacaoObj->valorPadrao->agencia_maritima)? '' : parserValor($importacaoObj->valorPadrao->agencia_maritima),
            'laudo_previsto' => empty($importacaoObj->valorPadrao->laudo)? '' : parserValor($importacaoObj->valorPadrao->laudo),
            'seguro_previsto' => empty($importacaoObj->valorPadrao->seguro)? '' : parserValor($importacaoObj->valorPadrao->seguro),
            'armazenagem_previsto' => empty($importacaoObj->valorPadrao->armazenagem)? '' : parserValor($importacaoObj->valorPadrao->armazenagem),
            'outras_despesas_previsto' => empty($importacaoObj->valorPadrao->outras_despesas)? '' : parserValor($importacaoObj->valorPadrao->outras_despesas),
            'total_previsto' => empty($importacaoObj->valorPadraoTotal->total)? '' : parserValor($importacaoObj->valorPadraoTotal->total+$total_cambio_previsto+$pis_previsto+$cofins_previsto),
            'dolar_referencia' => empty($importacaoValorPadraoObj->dolar_referencia)? '' : parserValor($importacaoValorPadraoObj->dolar_referencia),
            'transporte_rodoviario_previsto' => empty($importacaoValorPadraoObj->frete_rodoviario)? '' : parserValor($importacaoValorPadraoObj->frete_rodoviario),
            'icms_saida_previsto' => empty($icms_saida_previsto)? '' : parserValor($icms_saida_previsto),
            'icms_a_pagar_previsto' => empty($icms_a_pagar)? '' : parserValor($icms_a_pagar),
        ];

        if($total['preco_fob'] > $total['preco_contabil']){
            $total['diferenca_preco'] = parserValor($total['preco_fob'] - $total['preco_contabil']);
        }else{
            $total['diferenca_preco'] = empty($total['preco_contabil'] - $total['preco_fob'])? '' : parserValor($total['preco_contabil'] - $total['preco_fob']);
        }
        $total['quantidade'] = empty($total['quantidade'])? '' : parserValor($total['quantidade']);
        $total['quantidade_realizada'] = empty($total['quantidade_realizada'])? '' : parserValor($total['quantidade_realizada']);
        $total['preco_fob'] = empty($total['preco_fob'])? '' : parserValor($total['preco_fob']);
        $total['preco_contabil'] = empty($total['preco_contabil'])? '' : parserValor($total['preco_contabil']);

        $dados_follow = [
            'tipo_cor' => '',
            'envio_cor_data_previsao' => '',
            'envio_cor_data_envio' => '',
            'envio_cor_data_recebido' => '',
            'quality_sample_aprovacao' => '',
            'quality_sample_data_previsao_envio' => '',
            'quality_sample_data_envio' => '',
            'quality_sample_data_recebido' => '',
            'quality_sample_data_previsao_aprovacao' => '',
            'quality_sample_data_aprovacao' => '',
            'laboratorio_aprovacao' => '',
            'laboratorio_data_previsao_envio' => '',
            'laboratorio_data_envio' => '',
            'laboratorio_data_recebido' => '',
            'laboratorio_data_aprovacao' => '',
            'tempo_producao_previsao_termino' => '',
            'tempo_producao_termino' => '',
            'amostra_embarque_aprovacao' => '',
            'amostra_embarque_data_previsao_envio' => '',
            'amostra_embarque_data_envio' => '',
            'amostra_embarque_data_recebido' => '',
            'amostra_embarque_data_previsao_aprovacao' => '',
            'amostra_embarque_data_aprovacao' => '',
            'autorizacao_embarque_data_previsao_envio' => '',
            'autorizacao_embarque_data_envio' => '',
            'envio_cor_data_revisao' => '',
            'quality_sample_transportadora' => '',
            'quality_sample_awb' => '',
            'amostra_embarque_transportadora' => '',
            'amostra_embarque_awb' => '', 
        ];

        $lancamentos = [];
        $lancamento_total = 0;
        $lancamento_real_total = 0;
        $previstos = [];
        if(!empty($importacaoObj->financeiro)){
            if(!empty($importacaoObj->financeiro->lancamentos)){
                foreach($importacaoObj->financeiro->lancamentos as $lancamento){
                    if($lancamento->previsto === false){
                        $lancamentos[] = [
                            "id" => encrypt($lancamento->id),
                            "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                            "cambio_data" => parserData($lancamento->cambio_data),
                            "cambio_valor" => parserValor($lancamento->cambio_valor),
                            "real_taxa" => parserValor4CasasDecimais($lancamento->real_taxa),
                            "real_valor" => parserValor($lancamento->real_valor),
                            "banco" => $lancamento->banco,
                            "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                            "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                            "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                            "modalidade" => str_replace("_", " ", $lancamento->modalidade),
                        ];

                        $lancamento_total += $lancamento->cambio_valor;
                        $lancamento_real_total += $lancamento->real_valor;
                    }else{
                        if(in_array($lancamento->modalidade, ['a_prazo', 'a_vista'])){
                            $index = 'parcela';
                        }else{
                            $index = $lancamento->modalidade;
                        }
                        
                        if($index == 'parcela'){
                            $previstos[$index][] = [
                                "id" => encrypt($lancamento->id),
                                "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                "real_valor" => parserValor($lancamento->real_valor),
                                "banco" => $lancamento->banco,
                                "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                "modalidade" => 'Pagamento Fornecedor',
                                "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                            ];

                            $parcelas++;
                        }else{
                            $previstos[$index] = [
                                "id" => encrypt($lancamento->id),
                                "importacao_financeiros_id" => encrypt($lancamento->importacao_financeiros_id),
                                "cambio_data" => empty($lancamento->cambio_data)? '' : parserData($lancamento->cambio_data),
                                "cambio_valor" => empty($lancamento->cambio_valor)? '' : parserValor($lancamento->cambio_valor),
                                "real_taxa" => empty($lancamento->real_taxa)? '' : parserValor4CasasDecimais($lancamento->real_taxa),
                                "real_valor" => empty($lancamento->real_valor)? '' : parserValor($lancamento->real_valor),
                                "banco" => $lancamento->banco,
                                "fechamento_tipo" => str_replace("_", " ", $lancamento->fechamento_tipo),
                                "cambio_numero_contrato" => $lancamento->cambio_numero_contrato,
                                "banco_numero_contrato" => $lancamento->banco_numero_contrato,
                                "modalidade" => str_replace("_", " ", $lancamento->modalidade),
                                "baixa_data" => empty($lancamento->baixa_data)? '' : parserData($lancamento->baixa_data),
                            ];
                        }                        
                    }
                }
            }
        }
        $lancamento_total = empty($lancamento_total)? '' : parserValor($lancamento_total);
        $lancamento_real_total = empty($lancamento_real_total)? '' : parserValor($lancamento_real_total);

        $dados_follow = [];
        $tipo = $this->dadosTipoCores();
        $tipo_aprovacoes_simples = $this->dadosSimplesAprovacao();
        $tipo_aprovacoes_parcial = $this->dadosAprovacaoParcial();
        if(empty($importacaoObj->followUpDetalhes)){
            $importacaoFollowUpObj = new ImportacaoFollowUp;
            $importacaoFollowUpObj->importacaos_id = $id;
            $importacaoFollowUpObj->created_by = Auth::id();
            $importacaoFollowUpObj->save();
        }else{
            foreach($importacaoObj->followUpDetalhesItens as $importacao_follow_up){
                $dados_follow[] = [
                    'tipo_cor' => empty($importacao_follow_up->tipo_cor)? '' : $tipo[$importacao_follow_up->tipo_cor],
                    'envio_cor_data_previsao' => empty($importacao_follow_up->envio_cor_data_previsao)? '' : $importacao_follow_up->envio_cor_data_previsao->format('d/m/Y'), 
                    'envio_cor_data_envio' => empty($importacao_follow_up->envio_cor_data_envio)? '' : $importacao_follow_up->envio_cor_data_envio->format('d/m/Y'), 
                    'envio_cor_data_recebido' => empty($importacao_follow_up->envio_cor_data_recebido)? '' : $importacao_follow_up->envio_cor_data_recebido->format('d/m/Y'), 
                    'quality_sample_aprovacao' => empty($importacao_follow_up->quality_sample_aprovacao)? '' : $tipo_aprovacoes_simples[$importacao_follow_up->quality_sample_aprovacao], 
                    'quality_sample_data_previsao_envio' => empty($importacao_follow_up->quality_sample_data_previsao_envio)? '' : $importacao_follow_up->quality_sample_data_previsao_envio->format('d/m/Y'), 
                    'quality_sample_data_envio' => empty($importacao_follow_up->quality_sample_data_envio)? '' : $importacao_follow_up->quality_sample_data_envio->format('d/m/Y'), 
                    'quality_sample_data_recebido' => empty($importacao_follow_up->quality_sample_data_recebido)? '' : $importacao_follow_up->quality_sample_data_recebido->format('d/m/Y'), 
                    'quality_sample_data_previsao_aprovacao' => empty($importacao_follow_up->quality_sample_data_previsao_aprovacao)? '' : $importacao_follow_up->quality_sample_data_previsao_aprovacao->format('d/m/Y'), 
                    'quality_sample_data_aprovacao' => empty($importacao_follow_up->quality_sample_data_aprovacao)? '' : $importacao_follow_up->quality_sample_data_aprovacao->format('d/m/Y'), 
                    'laboratorio_aprovacao' => empty($importacao_follow_up->laboratorio_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->laboratorio_aprovacao], 
                    'laboratorio_data_previsao_envio' => empty($importacao_follow_up->laboratorio_data_previsao_envio)? '' : $importacao_follow_up->laboratorio_data_previsao_envio->format('d/m/Y'), 
                    'laboratorio_data_envio' => empty($importacao_follow_up->laboratorio_data_envio)? '' : $importacao_follow_up->laboratorio_data_envio->format('d/m/Y'), 
                    'laboratorio_data_recebido' => empty($importacao_follow_up->laboratorio_data_recebido)? '' : $importacao_follow_up->laboratorio_data_recebido->format('d/m/Y'), 
                    'laboratorio_data_aprovacao' => empty($importacao_follow_up->laboratorio_data_aprovacao)? '' : $importacao_follow_up->laboratorio_data_aprovacao->format('d/m/Y'), 
                    'tempo_producao_previsao_termino' => empty($importacao_follow_up->tempo_producao_previsao_termino)? '' : $importacao_follow_up->tempo_producao_previsao_termino->format('d/m/Y'), 
                    'tempo_producao_termino' => empty($importacao_follow_up->tempo_producao_termino)? '' : $importacao_follow_up->tempo_producao_termino->format('d/m/Y'), 
                    'amostra_embarque_aprovacao' => empty($importacao_follow_up->amostra_embarque_aprovacao)? '' : $tipo_aprovacoes_parcial[$importacao_follow_up->amostra_embarque_aprovacao], 
                    'amostra_embarque_data_previsao_envio' => empty($importacao_follow_up->amostra_embarque_data_previsao_envio)? '' : $importacao_follow_up->amostra_embarque_data_previsao_envio->format('d/m/Y'), 
                    'amostra_embarque_data_envio' => empty($importacao_follow_up->amostra_embarque_data_envio)? '' : $importacao_follow_up->amostra_embarque_data_envio->format('d/m/Y'), 
                    'amostra_embarque_data_recebido' => empty($importacao_follow_up->amostra_embarque_data_recebido)? '' : $importacao_follow_up->amostra_embarque_data_recebido->format('d/m/Y'), 
                    'amostra_embarque_data_previsao_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_previsao_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_previsao_aprovacao->format('d/m/Y'), 
                    'amostra_embarque_data_aprovacao' => empty($importacao_follow_up->amostra_embarque_data_aprovacao)? '' : $importacao_follow_up->amostra_embarque_data_aprovacao->format('d/m/Y'), 
                    'autorizacao_embarque_data_previsao_envio' => empty($importacao_follow_up->autorizacao_embarque_data_previsao_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_previsao_envio->format('d/m/Y'), 
                    'autorizacao_embarque_data_envio' => empty($importacao_follow_up->autorizacao_embarque_data_envio)? '' : $importacao_follow_up->autorizacao_embarque_data_envio->format('d/m/Y'), 
                    'envio_cor_data_revisao' => empty($importacao_follow_up->envio_cor_data_revisao)? '' : $importacao_follow_up->envio_cor_data_revisao->format('d/m/Y'),
                    'quality_sample_transportadora' => empty($importacao_follow_up->quality_sample_transportadora)? '' : $importacao_follow_up->quality_sample_transportadora,
                    'quality_sample_awb' => empty($importacao_follow_up->quality_sample_awb)? '' : $importacao_follow_up->quality_sample_awb,
                    'amostra_embarque_transportadora' => empty($importacao_follow_up->amostra_embarque_transportadora)? '' : $importacao_follow_up->amostra_embarque_transportadora,
                    'amostra_embarque_awb' => empty($importacao_follow_up->amostra_embarque_awb)? '' : $importacao_follow_up->amostra_embarque_awb,
                    'produto' => $importacao_follow_up->produto_codigo.' - '.$importacao_follow_up->detalhesProduto->descricao,
                ];
            }
        }
        $nota_importacao_numero = '';
        $nota_remessa_numero = '';
        if(!empty($importacaoObj->pedidoCompras->notas[0])){
            $nota_importacao_numero = $importacaoObj->pedidoCompras->notas[0]->notasentradas[0]["Número do Documento"];

            if(!empty($importacaoObj->pedidoCompras->notas[0]->notasentradas[0]->documentosAssociacoes[0])){
                $nota_saida_obj = $importacaoObj->pedidoCompras->notas[0]->notasentradas[0]->documentosAssociacoes[0]->notaSaida;
                $nota_remessa_numero = $nota_saida_obj->numero;

                $icms_a_pagar =10;// parserValor($nota_saida_obj->valor * (0.6/100));
                $icms_saida =20;// parserValor($nota_saida_obj->valor * (4/100));
            }
        }      

        $total_custo_realizado = $importacaoObj->custos->ii + $importacaoObj->custos->ipi + $importacaoObj->custos->pis + $importacaoObj->custos->cofins + $importacaoObj->custos->afrmm + $importacaoObj->custos->taxa_siscomex + $importacaoObj->custos->sda + $importacaoObj->custos->honorarios + $importacaoObj->custos->expediente + $importacaoObj->custos->valor_li + $importacaoObj->custos->agencia_maritima + $importacaoObj->custos->armazem + $importacaoObj->custos->laudo + $importacaoObj->custos->outras_despesas + $importacaoObj->custos->icms_saida + $importacaoObj->custos->seguro + $importacaoObj->custos->transporte_rodoviario + $outras_despesas_total;
        $total_cambio = empty($importacaoObj->financeiro->valorCambioTotal->total)? 0 : $importacaoObj->financeiro->valorCambioTotal->total;
        $custo_diferenca = [
            'total_cambio_diferenca' => empty($total_cambio - $total_cambio_previsto)? '' : parserValor($total_cambio - $total_cambio_previsto),
            'ii_diferenca' => empty($importacaoObj->custos->ii - $ii_previsto)? '' : parserValor($importacaoObj->custos->ipi - $ii_previsto),
            'ipi_diferenca' => empty($importacaoObj->custos->ipi - $importacaoObj->valorPadrao->ipi)? '' : parserValor($importacaoObj->custos->ipi - $ii_previsto),
            'pis_diferenca' => empty($importacaoObj->custos->pis - $pis_previsto)? '' : parserValor($importacaoObj->custos->pis - $pis_previsto),
            'cofins_diferenca' => empty($importacaoObj->custos->cofins - $cofins_previsto)? '' : parserValor($importacaoObj->custos->cofins),
            'afrmm_diferenca' => empty($importacaoObj->custos->afrmm - $importacaoObj->valorPadrao->afrmm)? '' : parserValor($importacaoObj->custos->afrmm - $importacaoObj->valorPadrao->afrmm),
            'taxa_siscomex_diferenca' => empty($importacaoObj->custos->taxa_siscomex - $importacaoObj->valorPadrao->taxa_siscomex)? '' : parserValor($importacaoObj->custos->taxa_siscomex - $importacaoObj->valorPadrao->taxa_siscomex),
            'sda_diferenca' => empty($importacaoObj->custos->sda - $importacaoObj->valorPadrao->sda)? '' : parserValor($importacaoObj->custos->sda - $importacaoObj->valorPadrao->sda),
            'honorarios_diferenca' => empty($importacaoObj->custos->honorarios - $importacaoObj->valorPadrao->honorarios)? '' : parserValor($importacaoObj->custos->honorarios - $importacaoObj->valorPadrao->honorarios),
            'expediente_diferenca' => empty($importacaoObj->custos->expediente - $importacaoObj->valorPadrao->expediente)? '' : parserValor($importacaoObj->custos->expediente - $importacaoObj->valorPadrao->expediente),
            'valor_li_diferenca' => empty($importacaoObj->custos->valor_li - $importacaoObj->valorPadrao->valor_li)? '' : parserValor($importacaoObj->custos->valor_li - $importacaoObj->valorPadrao->valor_li),
            'agencia_maritima_diferenca' => empty($importacaoObj->custos->agencia_maritima - $importacaoObj->valorPadrao->agencia_maritima)? '' : parserValor($importacaoObj->custos->agencia_maritima - $importacaoObj->valorPadrao->agencia_maritima),
            'laudo_diferenca' => empty($importacaoObj->custos->laudo - $importacaoObj->valorPadrao->laudo)? '' : parserValor($importacaoObj->custos->laudo - $importacaoObj->valorPadrao->laudo),
            'icms_saida_diferenca' => empty($importacaoObj->custos->icms_saida - $importacaoObj->valorPadrao->icms_saida)? '' : parserValor($importacaoObj->custos->icms_saida - $importacaoObj->valorPadrao->icms_saida),
            'seguro_diferenca' => empty($importacaoObj->custos->seguro - $importacaoObj->valorPadrao->seguro)? '' : parserValor($importacaoObj->custos->seguro - $importacaoObj->valorPadrao->seguro),
            'armazenagem_diferenca' => empty($importacaoObj->custos->armazem - $importacaoObj->valorPadrao->armazenagem)? '' : parserValor($importacaoObj->custos->armazem - $importacaoObj->valorPadrao->armazenagem),
            'outras_despesas_diferenca' => empty($importacaoObj->custos->outras_despesas - $importacaoObj->valorPadrao->outras_despesas)? '' : parserValor($importacaoObj->custos->outras_despesas - $importacaoObj->valorPadrao->outras_despesas),
            'total_diferenca' => empty($total_custo_realizado - ($importacaoObj->valorPadraoTotal->total+$total_cambio_previsto+$pis_previsto+$cofins_previsto))? '' : parserValor($total_custo_realizado - ($importacaoObj->valorPadraoTotal->total+$total_cambio_previsto+$pis_previsto+$cofins_previsto)),
            'transporte_rodoviario_diferenca' => empty($importacaoObj->custos->transporte_rodoviario - $importacaoValorPadraoObj->frete_rodoviario)? '' : parserValor($importacaoObj->custos->transporte_rodoviario - $importacaoValorPadraoObj->frete_rodoviario),
        ];

        $dados = [
            'id' => encrypt($id),
            'fornecedor' => $importacaoObj->fornecedor->nome.' - '.$importacaoObj->fornecedor->cnpj_cpf,
            'proforma' => $importacaoObj->numero_proforma,
            'pcmn' => $importacaoObj->pedido_compras,
            'referencia' => $importacaoObj->referencia,
            'data_proforma' => parserData($importacaoObj->data_proforma),
            'data_previsao_carta_programa' => empty($importacaoObj->data_previsao_carta_programa)? '' : parserData($importacaoObj->data_previsao_carta_programa),
            'id_nota' => encrypt($importacaoObj->pedidoCompras->id_nota),
            'estabelecimento_codigo' => $importacaoObj->estabelecimento_codigo,
            'data_previsao_recebimento' => parserData($importacaoObj->pedidoCompras->previsao_entrega),
            'respresentante' => empty($importacaoObj->respresentante)? '' : $importacaoObj->respresentante->nome.' - '.$importacaoObj->respresentante->cnpj_cpf,
            'itens' => $itens,
            'data_carga_pronta_previsao' => empty($importacaoObj->data_carga_pronta_previsao)? '' : parserData($importacaoObj->data_carga_pronta_previsao),
            'data_carga_pronta_realizado' => empty($importacaoObj->data_carga_pronta_realizado)? '' : parserData($importacaoObj->data_carga_pronta_realizado),
            'data_embarque_previsao' => empty($importacaoObj->data_embarque_previsao)? '' : parserData($importacaoObj->data_embarque_previsao),
            'data_embarque_realizado' => empty($importacaoObj->data_embarque_realizado)? '' : parserData($importacaoObj->data_embarque_realizado),
            'data_chegada_porto_previsao' => empty($importacaoObj->data_chegada_porto_previsao)? '' : parserData($importacaoObj->data_chegada_porto_previsao),
            'data_chegada_porto_realizado' => empty($importacaoObj->data_chegada_porto_realizado)? '' : parserData($importacaoObj->data_chegada_porto_realizado),
            'data_di_previsao' => empty($importacaoObj->data_di_previsao)? '' : parserData($importacaoObj->data_di_previsao),
            'data_di_realizado' => empty($importacaoObj->data_di_realizado)? '' : parserData($importacaoObj->data_di_realizado),
            'data_devolucao_cntr_previsao' => empty($importacaoObj->data_devolucao_cntr_previsao)? '' : parserData($importacaoObj->data_devolucao_cntr_previsao),
            'data_devolucao_cntr_realizado' => empty($importacaoObj->data_devolucao_cntr_realizado)? '' : parserData($importacaoObj->data_devolucao_cntr_realizado),
            'data_quality_sample_enviado' => empty($importacaoObj->data_quality_sample_enviado)? '' : parserData($importacaoObj->data_quality_sample_enviado),
            'data_quality_sample_recebido' => empty($importacaoObj->data_quality_sample_recebido)? '' : parserData($importacaoObj->data_quality_sample_recebido),
            'data_handlooms_enviado' => empty($importacaoObj->data_handlooms_enviado)? '' : parserData($importacaoObj->data_handlooms_enviado),
            'data_handlooms_recebido' => empty($importacaoObj->data_handlooms_recebido)? '' : parserData($importacaoObj->data_handlooms_recebido),
            'data_strike_off_enviado' => empty($importacaoObj->data_strike_off_enviado)? '' : parserData($importacaoObj->data_strike_off_enviado),
            'data_strike_off_recebido' => empty($importacaoObj->data_strike_off_recebido)? '' : parserData($importacaoObj->data_strike_off_recebido),
            'data_amostra_embarque_enviado' => empty($importacaoObj->data_amostra_embarque_enviado)? '' : parserData($importacaoObj->data_amostra_embarque_enviado),
            'data_amostra_embarque_recebido' => empty($importacaoObj->data_amostra_embarque_recebido)? '' : parserData($importacaoObj->data_amostra_embarque_recebido),
            'aprovacao_amostra_embarque' => $aprovacao_amostra_embarque,
            'aprovado_embarque_produto_codigo' => $aprovado_embarque_produto_codigo,
            'aprovado_embarque_produto' => $aprovado_embarque_produto,
            'tempo_producao' => empty($importacaoObj->tempo_producao)? '' : $importacaoObj->tempo_producao,
            'arquivo_carta_programada' => $arquivo_carta_programada,
            'total' => $total,
            'porto_origem' => empty($porto_origem)? '' : $porto_origem,
            'porto_destino' => empty($porto_destino)? '' : $porto_destino,
            'agente_compra' => empty($agente_compra)? '' : $agente_compra,
            'armador' => empty($armador)? '' : $armador,
            'etd_booking' => $etd_booking,
            'eta_booking' => $eta_booking,
            'numero_bl' => empty($numero_bl)? '' : $numero_bl,
            'transit_time_chegada' => empty($transit_time_chegada)? '' : parserData($transit_time_chegada),
            'transit_time_saida' => empty($transit_time_saida)? '' : parserData($transit_time_saida),
            'arquivos' => $arquivos,
            'ii' => empty($ii)? '' : $ii,
            'ipi' => empty($ipi)? '' : $ipi,
            'pis' => empty($pis)? '' : $pis,
            'cofins' => empty($cofins)? '' : $cofins,
            'afrmm' => empty($afrmm)? '' : $afrmm,
            'taxa_siscomex' => empty($taxa_siscomex)? '' : $taxa_siscomex,
            'sda' => empty($sda)? '' : $sda,
            'honorarios' => empty($honorarios)? '' : $honorarios,
            'expediente' => empty($expediente)? '' : $expediente,
            'valor_li' => empty($valor_li)? '' : $valor_li,
            'agencia_maritima' => empty($agencia_maritima)? '' : $agencia_maritima,
            'armazem' => empty($armazem)? '' : $armazem,
            'laudo' => empty($laudo)? '' : $laudo,
            'outras_despesas' => empty($outras_despesas)? '' : $outras_despesas,
            'icms_saida' => empty($icms_saida)? '' :  parserValor($icms_saida),
            'seguro' => empty($seguro)? '' : $seguro,
            'transporte_rodoviario' => empty($transporte_rodoviario)? '' : $transporte_rodoviario,
            'data_embarque' => empty($data_embarque)? '' : parserData($data_embarque),
            'debito_credito' => empty($debito_credito)? '' : parserValor($debito_credito),
            'valor_fob_pago' => parserValor($valor_fob_pago),
            'valor_fob_devido' => parserNumber($total['preco_fob'])-$valor_fob_pago <= 0? 0 : parserValor(parserNumber($total['preco_fob'])-$valor_fob_pago),
            'outras_despesas' => $outras_despesas,
            'outras_despesas_total' => parserValor($outras_despesas_total),
            'custo_previsto' => $custo_previsto,
            'total_cambio_realizado' => $total_cambio_realizado,
            'dados_follow' => $dados_follow,
            'tipos_cores' => $importacaoDadoComplemetarFollowUpControllerObj->dadosTipoCores(),
            'tipo_aprovacoes_simples' => $importacaoDadoComplemetarFollowUpControllerObj->dadosSimplesAprovacao(),
            'tipo_aprovacoes_parcial' => $importacaoDadoComplemetarFollowUpControllerObj->dadosAprovacaoParcial(),
            'id_follow_up' => encrypt($importacaoObj->followUpDetalhes->id),
            'icms_a_pagar' =>  empty($icms_a_pagar)? '' :  parserValor($icms_a_pagar),
            'nota_importacao_numero' => $nota_importacao_numero,
            'nota_remessa_numero' => $nota_remessa_numero,
            'cotacao_dolar' => empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $importacaoValorPadraoObj->dolar_referencia,
            'lancamento_total' => $lancamento_total,
            'lancamento_real_total' => $lancamento_real_total,
            'lancamentos' => $lancamentos,
            'custo_diferenca' => $custo_diferenca,
            'total_custo_realizado' => parserValor($total_custo_realizado),
            'previstos' => $previstos,
        ];
     
        return view('programs.importacao.modal.detalhes')->with(['dados' => $dados]);
    }

    public function modalHistoricoDolarReferencia(){
        $query = ImportacaoValorPadrao::select()->withTrashed();
        $query->limit(100);
        $result = $query->get();

        $retorno = [];
        foreach($result as $valor){
            $retorno[] = [
                'data' => $valor->created_at->format('d/m/Y H:i'),
                'valor_dolar' => parserValor($valor->dolar_referencia),
            ];
        }

        return view('programs.importacao.modal.historico_dolar')->with(['valores_dolar' => $retorno]);
    }

    public function modalAlteracaoDataEtdEta(Request $request){
        $fields = $request->only('tipo');
        
        return view('programs.importacao.modal.alteracao_data')->with(['tipo' => $fields['tipo']]);
    }

    public function adicionarMudancaValor(ImportacaoAdicionarMudancaValorRequest $request){
        $fields = $request->only('id', 'campo', 'valor', 'tipo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        if(!empty($fields['tipo'])){
            if($fields['tipo'] === 'data'){
                if(!empty($fields['valor'])){
                    $data_valor = Carbon::createFromFormat('d/m/Y', $fields['valor'])->setTime(0,0,0);
                }else{
                    $data_valor = null;
                }
            }
       }

        $importacaoObj = Importacao::find($id);
        if($fields['campo'] == 'referencia'){
            $importacaoObj->referencia = $fields['valor'];
        }else if($fields['campo'] == 'previsao_carta_programa'){
            $importacaoObj->data_previsao_carta_programa = $data_valor;
        }else if($fields['campo'] == 'respresentante'){
            if(!empty($fields['valor'])){
                $respresentante = FornecedorNasajon::select();
                $respresentante->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['valor']));
                $respresentante = $respresentante->first();
            }else{
                $respresentante = null;
            }
            
            $importacaoObj->respresentante_codigo = $respresentante->codigo;
        }else if($fields['campo'] == 'carga_pronta_previsao'){
            $importacaoObj->data_carga_pronta_previsao = $data_valor;
        }else if($fields['campo'] == 'carga_pronta_realizado'){
            if(empty($data_valor)){
                $importacaoObj->data_carga_pronta_realizado = $data_valor;
                $importacaoObj->data_embarque_realizado = $data_valor;
                $importacaoObj->data_chegada_porto_realizado = $data_valor;
                $importacaoObj->data_di_realizado = $data_valor;
                $importacaoObj->data_devolucao_cntr_realizado = $data_valor;
            }else{
                $importacaoObj->data_carga_pronta_realizado = $data_valor;
            }
        }else if($fields['campo'] == 'embarque_previsao'){
            $importacaoObj->data_embarque_previsao = $data_valor;
        }else if($fields['campo'] == 'data_embarque_etd'){
            if(empty($data_valor)){
                $importacaoObj->data_embarque_realizado = $data_valor;
                $importacaoObj->data_chegada_porto_realizado = $data_valor;
                $importacaoObj->data_di_realizado = $data_valor;
                $importacaoObj->data_devolucao_cntr_realizado = $data_valor;
            }else{
                $importacaoObj->data_embarque_realizado = $data_valor;
            }
        }else if($fields['campo'] == 'chegada_porto_previsao'){
            $importacaoObj->data_chegada_porto_previsao = $data_valor;
        }else if($fields['campo'] == 'data_chegada_eta'){
            if(empty($data_valor)){
                $importacaoObj->data_chegada_porto_realizado = $data_valor;
                $importacaoObj->data_di_realizado = $data_valor;
                $importacaoObj->data_devolucao_cntr_realizado = $data_valor;
            }else{
                $importacaoObj->data_chegada_porto_realizado = $data_valor;
            }
        }else if($fields['campo'] == 'data_di_previsao'){
            $importacaoObj->data_di_previsao = $data_valor;
        }else if($fields['campo'] == 'data_di_realizado'){
            if(empty($data_valor)){
                $importacaoObj->data_di_realizado = $data_valor;
                $importacaoObj->data_devolucao_cntr_realizado = $data_valor;
            }else{
                $importacaoObj->data_di_realizado = $data_valor;
            }
        }else if($fields['campo'] == 'devolucao_cntr_previsao'){
            $importacaoObj->data_devolucao_cntr_previsao = $data_valor;
        }else if($fields['campo'] == 'devolucao_cntr_realizado'){
            $importacaoObj->data_devolucao_cntr_realizado = $data_valor;
        }else if($fields['campo'] == 'quality_sample_enviado'){
            $importacaoObj->data_quality_sample_enviado = $data_valor;
        }else if($fields['campo'] == 'quality_sample_recebido'){
            $importacaoObj->data_quality_sample_recebido = $data_valor;
        }else if($fields['campo'] == 'handlooms_enviado'){
            $importacaoObj->data_handlooms_enviado = $data_valor;
        }else if($fields['campo'] == 'handlooms_recebido'){
            $importacaoObj->data_handlooms_recebido = $data_valor;
        }else if($fields['campo'] == 'strike_off_enviado'){
            $importacaoObj->data_strike_off_enviado = $data_valor;
        }else if($fields['campo'] == 'strike_off_recebido'){
            $importacaoObj->data_strike_off_recebido = $data_valor;
        }else if($fields['campo'] == 'amostra_embarque_enviado'){
            $importacaoObj->data_amostra_embarque_enviado = $data_valor;
        }else if($fields['campo'] == 'amostra_embarque_recebido'){
            $importacaoObj->data_amostra_embarque_recebido = $data_valor;
        }else if($fields['campo'] == 'frete_fornecedor'){
            $importacaoObj->frete_fornecedor = parserNumber($fields['valor']);
        }
        $importacaoObj->updated_by = Auth::id();
        $importacaoObj->save();

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $fields,
        ];

        return response()->json($response);
    }

    public function adicionarMudancaValorEmbarque(Request $request){
        $fields = $request->only('id', 'campo', 'valor', 'tipo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoEmbarqueObj = ImportacaoEmbarque::where('importacaos_id', $id)->first();
        if(empty($importacaoEmbarqueObj)){
            $importacaoEmbarqueObj = new ImportacaoEmbarque;
            $importacaoEmbarqueObj->importacaos_id = $id;
        }
        
        if($fields['campo'] == 'porto_origem'){
            $importacaoEmbarqueObj->porto_origem = $fields['valor'];
        }else if($fields['campo'] == 'porto_destino'){
            $importacaoEmbarqueObj->porto_destino = $fields['valor'];
        }else if($fields['campo'] == 'agente_compra'){
            $importacaoEmbarqueObj->agente_compra = $fields['valor'];
        }else if($fields['campo'] == 'armador'){
            $importacaoEmbarqueObj->armador = $fields['valor'];
        }else if($fields['campo'] == 'numero_bl'){
            $importacaoEmbarqueObj->numero_bl = $fields['valor'];
        }else if($fields['campo'] == 'etd_booking'){
            $importacaoEmbarqueObj->etd_booking = boolval($fields['valor']);

            if(boolval($fields['valor']) == false){
                $importacaoObj = Importacao::find($id);
                $importacaoObj->data_embarque_realizado = null;
                $importacaoObj->updated_by = Auth::id();
                $importacaoObj->save();
            }
        }else if($fields['campo'] == 'eta_booking'){
            $importacaoEmbarqueObj->eta_booking = boolval($fields['valor']);

            if(boolval($fields['valor']) == false){
                $importacaoObj = Importacao::find($id);
                $importacaoObj->data_chegada_porto_realizado = null;
                $importacaoObj->updated_by = Auth::id();
                $importacaoObj->save();
            }
        }
        $importacaoEmbarqueObj->updated_by = Auth::id();
        $importacaoEmbarqueObj->save();

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $fields,
        ];

        return response()->json($response);
    }

    public function adicionarMudancaValorFinanceiro(Request $request){
        $fields = $request->only('id', 'campo', 'valor', 'tipo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        if(!empty($fields['tipo'])){
            if($fields['tipo'] === 'data'){
                if(!empty($fields['valor'])){
                    $data_valor = Carbon::createFromFormat('d/m/Y', $fields['valor'])->setTime(0,0,0);
                }else{
                    $data_valor = null;
                }
            }
         }

        $importacaoFinanceiroObj = ImportacaoFinanceiro::where('importacaos_id', $id)->first();
        if(empty($importacaoFinanceiroObj)){
            $importacaoFinanceiroObj = new ImportacaoFinanceiro;
            $importacaoFinanceiroObj->importacaos_id = $id;
        }

        if($fields['campo'] == 'data_embarque_financeiro'){
            $importacaoFinanceiroObj->data_embarque = $data_valor;
        }
        $importacaoFinanceiroObj->updated_by = Auth::id();
        $importacaoFinanceiroObj->save();

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $fields,
        ];

        return response()->json($response);
    }

    public function adicionarMudancaValorCusto(Request $request){
        $fields = $request->only('id', 'campo', 'valor', 'codigo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $fields['valor'] = empty($fields['valor'])? null : parserNumber($fields['valor']);

        $importacaoCustoObj = ImportacaoCusto::where('importacaos_id', $id)->first();
        if(empty($importacaoCustoObj)){
            $importacaoCustoObj = new ImportacaoCusto;
            $importacaoCustoObj->importacaos_id = $id;
        }

        if($fields['campo'] == 'ii'){
            $importacaoCustoObj->ii = $fields['valor'];
        }else if($fields['campo'] == 'ipi'){
            $importacaoCustoObj->ipi = $fields['valor'];
        }else if($fields['campo'] == 'pis'){
            $importacaoCustoObj->pis = $fields['valor'];
        }else if($fields['campo'] == 'cofins'){
            $importacaoCustoObj->cofins = $fields['valor'];
        }else if($fields['campo'] == 'afrmm'){
            $importacaoCustoObj->afrmm = $fields['valor'];
        }else if($fields['campo'] == 'taxa_siscomex'){
            $importacaoCustoObj->taxa_siscomex = $fields['valor'];
        }else if($fields['campo'] == 'sda'){
            $importacaoCustoObj->sda = $fields['valor'];
        }else if($fields['campo'] == 'honorarios'){
            $importacaoCustoObj->honorarios = $fields['valor'];
        }else if($fields['campo'] == 'expediente'){
            $importacaoCustoObj->expediente = $fields['valor'];
        }else if($fields['campo'] == 'valor_li'){
            $importacaoCustoObj->valor_li = $fields['valor'];
        }else if($fields['campo'] == 'agencia_maritima'){
            $importacaoCustoObj->agencia_maritima = $fields['valor'];
        }else if($fields['campo'] == 'armazem'){
            $importacaoCustoObj->armazem = $fields['valor'];
        }else if($fields['campo'] == 'laudo'){
            $importacaoCustoObj->laudo = $fields['valor'];
        }else if($fields['campo'] == 'icms_saida'){
            $importacaoCustoObj->icms_saida = $fields['valor'];
        }else if($fields['campo'] == 'seguro'){
            $importacaoCustoObj->seguro = $fields['valor'];
        }else if($fields['campo'] == 'transporte_rodoviario'){
            $importacaoCustoObj->transporte_rodoviario = $fields['valor'];
        }        
        $importacaoCustoObj->updated_by = Auth::id();
        $importacaoCustoObj->save();

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => '',
        ];

        return response()->json($response);
    }

    public function adicionarMudancaValorProdutoContábil(Request $request){
        $fields = $request->only('id', 'campo', 'valor', 'codigo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoItemObj = ImportacaoItem::where('importacaos_id', $id)->where('produto_codigo', $fields['codigo'])->orderBy('id', 'desc')->first();

        if(is_null($importacaoItemObj)){
            $importacaoObj = Importacao::find($id);
            $comprasNasajon = ComprasNasajon::select('id_nota','cod_produto')->where('numero_pedido',$importacaoObj->pedido_compras)->where('cod_produto',$fields['codigo'])->first() ;

            $importacaoItemObj = new ImportacaoItem;
            $importacaoItemObj->importacaos_id = $importacaoObj->id;
            $importacaoItemObj->produto_codigo = $comprasNasajon->cod_produto;
            $importacaoItemObj->estabelecimento_codigo = $importacaoObj->estabelecimento_codigo;
            $importacaoItemObj->numero_proforma = $importacaoObj->numero_proforma;
            $importacaoItemObj->pedido_compras = $importacaoObj->pedido_compras;
            $importacaoItemObj->nota_uuid_nasajon = $comprasNasajon->id_nota;
            $importacaoItemObj->valor_contabil_unitario = empty($fields['valor'])? 0 : parserNumber($fields['valor']);
            $importacaoItemObj->updated_by = Auth::id();
            $importacaoItemObj->save();
        }else{

                $importacaoItemObj->valor_contabil_unitario = empty($fields['valor'])? 0 : parserNumber($fields['valor']);
                $importacaoItemObj->updated_by = Auth::id();
                $importacaoItemObj->save();
        }
    
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => '',
        ];

        return response()->json($response);
    }

    public function excluirOutrasDepesas(Request $request){
        $fields = $request->only('id');
        
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $importacaoCustoOutraDespesaObj = ImportacaoCustoOutraDespesa::find($id);

        $importacaos_id = $importacaoCustoOutraDespesaObj->importacaos_id;
        
        $importacaoCustoOutraDespesaObj->deleted_by = Auth::id();
        $importacaoCustoOutraDespesaObj->save();
        $importacaoCustoOutraDespesaObj->delete();

        $query = ImportacaoCustoOutraDespesa::select();
        $query->where('importacaos_id', $importacaos_id);
        $result = $query->get();
        $total = 0;
        $outras_despesas = [];
        foreach($result as $outra_depesa){
            $outras_despesas[] = [
                'id' => encrypt($outra_depesa->id),
                'descricao' => $outra_depesa->descricao,
                'valor' => parserValor($outra_depesa->valor),
            ];

            $total += $outra_depesa->valor;
        }

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'outras_despesas' => $outras_despesas,
                'total' => $total,
            ]
        ];

        return response()->json($response);
    }

    public function dadosTipoCores(){
        $tipos_cores = [
            'cores_unicas' => 'CORES UNICAS', 
            'estampado' => 'ESTAMPADO', 
            'fio_tinto' => 'FIO TINTO'
        ];

        return $tipos_cores;
    }

    public function dadosSimplesAprovacao(){
        $aprovacoes = [
            'aprovado' => 'Aprovado', 
            'reprovado' => 'Reprovado'
        ];

        return $aprovacoes;
    }




    public function dadosAprovacaoParcial(){
        $aprovacoes = [
            'aprovado_total' => 'Aprovado Total', 
            'aprovado_parcial' => 'Aprovado Parcial', 
            'reprovado' => 'Reprovado'
        ];

        return $aprovacoes;
    }



    public function situacaoPedido(){
        $situacao_pedido = [
            'Aberto' => 'Aberto', 
            'Aguardando Documento' => 'Aguardando Documento',
            'Cancelado' => 'Cancelado',
            'Liquidado' => 'Liquidado',
            'Parcialmente Liquidado' => 'Parcialmente Liquidado'

        ];

        return $situacao_pedido;
    }

    public function consultaPedidoAberto(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','2048M');
        $fields = $request->only('fornecedor_filtro', 'proforma_filtro', 'pedido_filtro', 'grupo', 'status','data_inicio', 'data_fim');

        $query = Importacao::select('importacaos.pedido_compras','importacaos.numero_proforma','importacaos.data_proforma','importacaos.data_embarque_realizado','importacaos.fornecedor_codigo','importacao_items.importacaos_id','produto_grupos.descricao as grupo',
        'quality_sample_aprovacao','laboratorio_aprovacao','amostra_embarque_aprovacao',
        DB::raw('max(envio_cor_data_envio) as envio_cor_data_envio, max(quality_sample_data_envio) as quality_sample_data_envio 
         ,max(laboratorio_data_envio) as laboratorio_data_envio,max(tempo_producao_termino) as tempo_producao_termino
          ,max(quality_sample_data_aprovacao) as quality_sample_data_aprovacao
         , max(autorizacao_embarque_data_envio) as autorizacao_embarque_data_envio'))->join('importacao_follow_ups','importacao_follow_ups.importacaos_id', '=', 'importacaos.id')
        ->join('importacao_items','importacao_items.importacaos_id', '=', 'importacaos.id')
        ->join('produto_especificacaos','importacao_items.produto_codigo', '=', 'produto_especificacaos.codigo_produto')
        ->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
        $query->groupBy('importacaos.pedido_compras','importacaos.numero_proforma','importacaos.data_proforma','importacaos.data_embarque_realizado','importacaos.fornecedor_codigo','importacao_items.importacaos_id','produto_grupos.descricao',
        'quality_sample_aprovacao','laboratorio_aprovacao','amostra_embarque_aprovacao');
 
        $query->with(['pedidoComprasItens' => function($query) use($fields){
            $query->whereIn('situacao',  ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'] );
            if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);;
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);;
                $query->whereBetween('previsao_entrega', [$data_inicial, $data_final]);
            }else if(!empty($fields['data_inicio'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);;
                $query->where('previsao_entrega', '>=', $data_inicial);
            }else if(!empty($fields['data_fim'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);;
                $query->where('previsao_entrega', '<=', $data_final);
            }
            $query->with(['produto']);
           
        },'fornecedor' => function($query) use($fields){
            if(!empty($fields['fornecedor_filtro'])){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }
        }]);

        if(!empty($fields['proforma_filtro'])){
            $query->where('importacaos.numero_proforma', $fields["proforma_filtro"]);
        }
        if(!empty($fields['pedido_filtro'])){
            $query->where('importacaos.pedido_compras', $fields["pedido_filtro"]);
        }
        if(!empty($fields['grupo'])){
            $query->where('grupo','ilike','%'. $fields["grupo"].'%');
        }
 
     try{
        $result = $query->get();
      
  
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => 'Dados não encontrados',
            'error' => [],
            'response' => []
        ]);
    }

        $retorno = [];
        $grupo_pedido ='';
 
        foreach($result as $importacao){
            if(!empty($importacao->fornecedor) && !empty($importacao->pedidoComprasItens[0])){
                $grupo =  ProdutoGrupo::where('descricao', $importacao->grupo)->first(); 
                $itens_grupo = $grupo->produtoEspecificacao->pluck('codigo_produto');
            
                $quantidade_grupo =  $importacao->pedidoComprasItens[0]->where('numero_pedido',$importacao->pedido_compras)->whereIn('cod_produto',$itens_grupo)->sum('quantidade');
      
                $retorno[] = [
                    'id' => encrypt($importacao->importacaos_id),
                    'id_nota' => encrypt($importacao->pedidoComprasItens[0]->id_nota),
                    'fornecedor' => $importacao->fornecedor->nome.' - '.$importacao->fornecedor->cnpj_cpf,
                    'pcmn' => $importacao->pedido_compras,
                    'grupo_produto' => $importacao->grupo,
                    'quantidade' => parserQtd($quantidade_grupo),
                    'unidade' => $importacao->pedidoComprasItens[0]->unidade_comercial,
                    'data_envio_cores' => !empty($importacao->envio_cor_data_envio) ? parserData($importacao->envio_cor_data_envio):'',
                    'proforma' => $importacao->numero_proforma,
                    'data_recebimento' =>!empty($importacao->data_proforma) ?  parserData($importacao->data_proforma):'',
                    'data_quatlity' =>!empty($importacao->quality_sample_data_aprovacao) ? parserData($importacao->quality_sample_data_aprovacao):'',
                    'status_quatlity' => $importacao->quality_sample_aprovacao,
                    'data_laboratorio' =>!empty($importacao->laboratorio_data_envio) ? parserData($importacao->laboratorio_data_envio):'',
                    'status_laboratorio' => $importacao->laboratorio_aprovacao,
                    'data_producao' =>!empty($importacao->tempo_producao_termino) ? parserData($importacao->tempo_producao_termino):'',
                    'status_producao' => $importacao->amostra_embarque_aprovacao,
                    'data_embarque_autorizacao' =>!empty($importacao->autorizacao_embarque_data_envio) ? parserData($importacao->autorizacao_embarque_data_envio):'',
                    'data_embarque' =>!empty($importacao->data_embarque_realizado) ? parserData($importacao->data_embarque_realizado):'',
      
      
                ];
            }
        }
     
 
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno
        ];

        return response()->json($response);

    }
    public function consultaCompraProduto(Request $request){
   
        $fields = $request->only('data_inicio', 'data_fim');

        $query = Importacao::select('importacaos.pedido_compras','importacaos.numero_proforma','importacaos.data_proforma','importacaos.fornecedor_codigo','importacao_items.importacaos_id','produto_grupos.descricao as grupo'
        ,'importacao_custos.pis' ,'importacao_custos.cofins' ,'importacao_custos.taxa_siscomex' ,'importacao_custos.sda' ,'importacao_custos.honorarios' ,'importacao_custos.expediente','importacao_custos.armazem' ,'importacao_custos.laudo')
        ->join('importacao_items','importacao_items.importacaos_id', '=', 'importacaos.id')
        ->join('importacao_custos','importacao_custos.importacaos_id', '=', 'importacaos.id')
        ->join('produto_especificacaos','importacao_items.produto_codigo', '=', 'produto_especificacaos.codigo_produto')
        ->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
        $query->groupBy('importacaos.pedido_compras','importacaos.numero_proforma','importacaos.data_proforma','importacaos.fornecedor_codigo','importacao_items.importacaos_id','produto_grupos.descricao'
        ,'importacao_custos.pis' ,'importacao_custos.cofins' ,'importacao_custos.taxa_siscomex' ,'importacao_custos.sda' ,'importacao_custos.honorarios' ,'importacao_custos.expediente','importacao_custos.armazem' ,'importacao_custos.laudo'
);
      $query->with(['pedidoCompras' => function($query) use($fields){


        $query->whereIn('situacao',  ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'] );
        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);;
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);;
            $query->whereBetween('previsao_entrega', [$data_inicial, $data_final]);
        }else if(!empty($fields['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio'])->setTime(0,0,0);;
            $query->where('previsao_entrega', '>=', $data_inicial);
        }else if(!empty($fields['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim'])->setTime(23,59,59);;
            $query->where('previsao_entrega', '<=', $data_final);
        }

        },'fornecedor']);

 

    try{    

        $result = $query->get();
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => 'Dados não encontrados',
            'error' => [],
            'response' => []
        ]);
    }

        $retorno = [];
        foreach($result as $importacao){
            if(!empty($importacao->pedidoCompras)){

                if(!empty($importacao->pedidoCompras->data_compra) && !empty($importacao->pedidoCompras->previsao_entrega) ){
                    $data_compra= Carbon::CreateFromFormat("Y-m-d", $importacao->pedidoCompras->data_compra);
                    $data_recebimento = Carbon::CreateFromFormat("Y-m-d",$importacao->pedidoCompras->previsao_entrega);
                    $dias_recebimento = $data_compra->diffInDays($data_recebimento);
                }else{
                    $data_compra=  $importacao->pedidoCompras->data_compra;
                    $data_recebimento='';
                      $dias_recebimento='';

                }
                $total_custo_realizado  =  $importacao->ii + $importacao->ipi + $importacao->pis + $importacao->cofins + $importacao->afrmm
                 +$importacao->taxa_siscomex     + $importacao->sda + $importacao->honorarios + $importacao->expediente +  $importacao->valor_li+
                 $importacao->agencia_maritima + $importacao->armazem + $importacao->laudo+ $importacao->outras_despesas +$importacao->icms_saida
                 + $importacao->seguro + $importacao->transporte_rodoviario  ;
              

               $outras_despesas_total= $importacao->custosOutraDespesa->where('importacaos_id', $importacao->importacaos_id)->sum('valor');
               if(!empty($outras_despesas_total)){
                    $custo_usd =  $total_custo_realizado  +  $outras_despesas_total;
               }else{
                     $custo_usd =  $total_custo_realizado;
               }
             
                $fob_usd = $importacao->pedidoCompras->where('situacao_item','<>',  'Cancelado')->where('numero_pedido', $importacao->pedido_compras)->sum('preco_compra');

                $retorno[] = [
                    'id' => encrypt($importacao->importacaos_id),
                    'id_nota' => encrypt($importacao->pedidoCompras->id_nota),
                    'fornecedor' => $importacao->fornecedor->nome.' - '.$importacao->fornecedor->cnpj_cpf,
                    'grupo' => $importacao->grupo,
                    'pcmn' => $importacao->pedido_compras,
                    'proforma' => $importacao->numero_proforma,
                    'quantidade_comprada' =>parserQTD($importacao->pedidoCompras->quantidade),
                    'quantidade_recebida' =>parserQTD($importacao->pedidoCompras->quantidade),
                    'unidade' => $importacao->pedidoCompras->unidade_comercial,
                    'data_compra' => parserData($data_compra),
                    'data_recebimento' =>!empty($data_recebimento) ?  parserData($data_recebimento):'',
                    'dias' =>$dias_recebimento,
                    'fob_usd' => parserValor( $fob_usd),
                    'custo_usd' => parserValor($custo_usd),

                ];
            }
        }
       
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno
        ];

        return response()->json($response);

    }

}
