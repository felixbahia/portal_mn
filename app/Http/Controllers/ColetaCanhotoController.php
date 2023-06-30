<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;

use App\ConfirmacaoNotaSaida;
use App\NotasNasajon;
use App\ClienteNasajon;
use App\NotasCfopNasajon;
use App\Http\Requests\ColetaCanhotoSalvarCanhotoRequest;
use App\Http\Requests\ColetaCanhotoBuscaNotaRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ColetaCanhotoController extends Controller
{
    public $path = 'public/canhotos/';
    private $estabelecimentos = '';

    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentosEmpty[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();
        $estabelecimentos = array_merge($estabelecimentosEmpty,$estabelecimentosReturn );
        $this->estabelecimentos = $estabelecimentos;
    }

    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ColetaCanhotoController") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ColetaCanhotoController');
        $codigo =  $request->only('codigo'); 
        $chave = '';
        $saida_codigo = (!empty($codigo['codigo'])) ? NotasNasajon::whereRaw("chavene ilike '".(strtolower($codigo['codigo'] ))."'")
        ->first() : '';

        if(!empty($codigo['codigo']) && !empty($saida_codigo)){
            $chave = $codigo['codigo'];
        }else if(!empty($codigo['codigo']) && empty($saida_codigo)){
            $chave = 'erro';
        }

        $dados = [];
        if(!empty($saida_codigo)){
            $cliente_codigo = empty($saida_codigo->cliente)? null : $saida_codigo->cliente->codigo;
            $confirmacao_nota = ConfirmacaoNotaSaida::where('nota',$saida_codigo->numero)
                ->where('cod_cliente',$cliente_codigo)
                ->first();
            $dados['canhoto'] = (!empty($confirmacao_nota->foto_canhoto)) ? Storage::url($confirmacao_nota->foto_canhoto) : '';
            $dados['cliente_nome'] = $saida_codigo->cliente_nome;
            $dados['cliente_documento'] = $saida_codigo->cliente_documento;
            $dados['estabelecimento_codigo'] = $saida_codigo->estabelecimento_codigo;
            $dados['estabelecimento_descricao'] = $saida_codigo->estabelecimento_detalhes->descricao;
            $dados['emissao'] = parserData($saida_codigo->emissao);
            $dados['datasaida'] = (!empty($confirmacao_nota)) ? parserData($confirmacao_nota->data_saida) : null;
            $dados['numero'] = $saida_codigo->numero;
            $dados['pesoliquido'] = (!empty($confirmacao_nota)) ? parserQtd3CasaDecimais($confirmacao_nota->peso) : 0;
        }
        return view('programs.coleta_canhoto.index')->with(['codigo' => $chave, 'dados' => $dados,'estabelecimentos' => $this->estabelecimentos]);
    }

    public function gravar(ColetaCanhotoSalvarCanhotoRequest $request){
        $inputs = $request->only('forma_busca','nota','estabelecimento','data_saida','peso','numero','cliente','documento_cliente','emissao','numero_cupom','estabelecimentos_cupom','uuid_cupom');
        
        $data_saida = Carbon::createFromFormat('d/m/Y', $inputs['data_saida'])->format('Y-m-d');
        $cliente = ClienteNasajon::select('codigo');

        if(!empty($inputs['documento_cliente'])){
            $cliente->where('cpf_cnpj',$inputs['documento_cliente']);
        }else{
            $cliente->where('nome','ilike','balcao');
        }

        $cliente = $cliente->first();

        $confirmacao_nota = ConfirmacaoNotaSaida::where('nota',$inputs['numero'])
        ->where('cod_cliente',$cliente->codigo);
        
        if($inputs['forma_busca'] === 'radio_cupom'){
            $confirmacao_nota->Where("nfce",true);
            $confirmacao_nota->Where("id_cupom",$inputs['uuid_cupom']);
        }

        $confirmacao_nota = $confirmacao_nota->first();

        if(!empty($confirmacao_nota)){
            if($request->hasFile('foto')){
                if(empty($confirmacao_nota->foto_canhoto)){
                    $foto = $confirmacao_nota->id . '.' . $request->file('foto')->getClientOriginalExtension();
                    $request->file('foto')->storeAs($this->path,  $foto);
                }else{
                    Storage::delete($confirmacao_nota->foto_canhoto);
                    $foto = $confirmacao_nota->id . '.' . $request->file('foto')->getClientOriginalExtension();
                    $request->file('foto')->storeAs($this->path,  $foto);
                }
                $confirmacao_nota->foto_canhoto = $this->path.$foto;
            }

            if($inputs['forma_busca'] === 'radio_cupom'){
                $confirmacao_nota->nfce = true;
            }

            if(!empty($inputs['estabelecimento'])){
                $confirmacao_nota->estabelecimento = $inputs['estabelecimento'];
            }

            if(!empty($inputs['peso'])){
                $confirmacao_nota->peso = (float) $inputs['peso'];
            }

            if(!empty($inputs['uuid_cupom'])){
                $confirmacao_nota->id_cupom = $inputs['uuid_cupom'];
            }

            $confirmacao_nota->data_saida = $data_saida;
            $confirmacao_nota->updated_by = Auth::id();
            $confirmacao_nota->save();
        }else{
            $confirmacao_nota = new ConfirmacaoNotaSaida;
            $confirmacao_nota->estabelecimento = $inputs['estabelecimento'];
            $confirmacao_nota->nota = $inputs['numero'];
            $confirmacao_nota->cod_cliente = $cliente->codigo;
            $confirmacao_nota->data_saida = $data_saida;
            $confirmacao_nota->peso = (!empty($inputs['peso'])) ? (float)$inputs['peso'] : '0';
            $confirmacao_nota->created_by = Auth::id();

            if($inputs['forma_busca'] === 'radio_cupom'){
                $confirmacao_nota->nfce = true;
            }
            
            if(!empty($inputs['uuid_cupom'])){
                $confirmacao_nota->id_cupom = $inputs['uuid_cupom'];
            }

            $confirmacao_nota->save();
            if($request->hasFile('foto')){
                $foto = $this->path.$confirmacao_nota->id . '.' . $request->file('foto')->getClientOriginalExtension();
                $request->file('foto')->storeAs($this->path,  $confirmacao_nota->id . '.' . $request->file('foto')->getClientOriginalExtension());
                $confirmacao_nota->foto_canhoto = $foto;
                $confirmacao_nota->save();
            }
        }   
        if(!$confirmacao_nota){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao gravar',
                'error' => '', 
                'response' => 'ok',
            ]);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => 'Registrado com Sucesso',
                'error' => '', 
                'response' => 'ok',
            ]);
        }
    }

    public function buscaNota(ColetaCanhotoBuscaNotaRequest $request){
        $fields = $request->only(["chave","numero_nota","estabelecimentos","forma_busca","numero_cupom","estabelecimentos_cupom","data_emissao_cupom","serie_cupom"]);
        
        $query = NotasNasajon::query();
        
        if($fields['forma_busca'] == 'chave'){
            $query->Where("chavene",$fields["chave"]);
        }else if($fields['forma_busca'] == 'numero'){
            if($fields["estabelecimentos"] == '9'){
                $estabelecimento = '20';
            }else{
                $estabelecimento = str_pad($fields["estabelecimentos"],2,'0', STR_PAD_LEFT);
            }
            $query->Where("numero",$fields["numero_nota"])
            ->where("estabelecimento_codigo",$estabelecimento);
        }else if($fields['forma_busca'] !== 'radio_cupom'){
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada',
                'error' => '', 
                'response' => 'erro',
            ]);
        }

        $query = $query->first();
        $dados = [];

        if($fields['forma_busca'] != 'radio_cupom' && !empty($query)){
            $confirmacao_nota = ConfirmacaoNotaSaida::where('nota',$query->numero)
                ->where('estabelecimento',str_pad($fields["estabelecimentos"],2,'0', STR_PAD_LEFT))
                ->first();

            $dados['canhoto'] = (!empty($confirmacao_nota->foto_canhoto)) ? Storage::url($confirmacao_nota->foto_canhoto) : '';
            $dados['cliente_nome'] = $query->cliente_nome;
            $dados['cliente_documento'] = $query->cliente_documento;
            $dados['estabelecimento_codigo'] = $query->estabelecimento_codigo;
            $dados['estabelecimento_descricao'] = $query->estabelecimento_detalhes->descricao;
            $dados['emissao'] = parserData($query->emissao);
            $dados['datasaida'] = (!empty($confirmacao_nota)) ? parserData($confirmacao_nota->data_saida) : null;
            $dados['numero'] = $query->numero;
            $dados['pesoliquido'] = (!empty($confirmacao_nota)) ? parserQtd3CasaDecimais($confirmacao_nota->peso) : 0;
            $dados['uuid'] = '';
            
        }else if($fields['forma_busca'] == 'radio_cupom'){
            if($fields["estabelecimentos"] == '9'){
                $estabelecimento = '20';
            }else{
                $estabelecimento = str_pad($fields["estabelecimentos_cupom"],2,'0', STR_PAD_LEFT);
            }

            $emissao_cupom = Carbon::createFromFormat('d/m/Y',$fields["data_emissao_cupom"])->format('Y-m-d');
            
            $cupom_nadajon = NotasCfopNasajon::with(['cliente'])
                ->where('estabelecimento_codigo',$estabelecimento)
                ->where('numero',$fields["numero_cupom"])
                ->where('modelo','ilike','NCE')
                ->where('emissao',$emissao_cupom)
                ->where('serie',$fields["serie_cupom"])
                ->first();

            if(empty($cupom_nadajon)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cupom não encontrada',
                    'error' => '', 
                    'response' => 'erro',
                ]);
            }
            
            $confirmacao_nota = ConfirmacaoNotaSaida::where('id_cupom',$cupom_nadajon->id)
            ->first();

            if(!empty($confirmacao_nota)){
                $dados['canhoto'] = (!empty($confirmacao_nota->foto_canhoto)) ? Storage::url($confirmacao_nota->foto_canhoto) : '';
            }else{
                $dados['canhoto'] = '';
            }
            
            $dados['cliente_nome'] = $cupom_nadajon->cliente_nome;
            $dados['cliente_documento'] = (!empty($cupom_nadajon->cliente_documento)) ? $cupom_nadajon->cliente_documento : '';
            $dados['estabelecimento_codigo'] = $cupom_nadajon->estabelecimento_codigo;
            $dados['estabelecimento_descricao'] = $cupom_nadajon->estabelecimento_descricao;
            $dados['emissao'] = parserData($cupom_nadajon->emissao);
            $dados['datasaida'] = (!empty($cupom_nadajon->datasaida)) ? parserData($cupom_nadajon->datasaida) : null;
            $dados['numero'] = $cupom_nadajon->numero;
            $dados['pesoliquido'] = parserQtd3CasaDecimais($cupom_nadajon->pesoliquido);
            $dados['uuid'] = $cupom_nadajon->id;
        }

        if(!empty($dados)){
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '', 
                'response' => $dados,
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada',
                'error' => '', 
                'response' => 'erro',
            ]);
        }
    }

}
