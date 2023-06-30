<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\RastreabilidadeFracoesNasajon;

use App\Http\Requests\RastreabilidadeFiltroRequest;

class RastreabilidadeController extends Controller
{
    private $situacao_peca = [];

    public function index(Request $request){
		if(Auth::user()->hasPermissionTo("programas App\Rastreabilidade") === false){
			return abort(403);
		}
		$request->session()->flash('model', 'App\Rastreabilidade');
		$estabelecimentos = returnEmpresasTodosNasajonView();
		return view("programs.reastreabilidade.index")->with(['estabelecimentos' => $estabelecimentos]);
	}

    public function filtro(RastreabilidadeFiltroRequest $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(500);
        $campos = $request->only(['estabelecimento','data_inicio','data_fim','fracao']);
        
        $rastreabilidade = RastreabilidadeFracoesNasajon::with(['fracaoNasajon']);

        if(!empty($campos['data_inicio'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->format('Y-m-d 00:00:00');
            $rastreabilidade->where('data_hora_criacao', '>=', $data_de);
        }

        if(!empty($campos['data_fim'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->format('Y-m-d 23:59:59');
            $rastreabilidade->where('data_hora_criacao', '<=', $data_ate);
        }

        if(!empty($campos['fracao'])){
            $rastreabilidade->where('codigo', 'ilike',$campos['fracao']);
        }

        if(!empty($campos['estabelecimento'])){
            $rastreabilidade->where('proprietario', str_pad($campos['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        $estabelecimentos = returnEmpresasNasajonView();
        $this->carregarSitucao();
        $situacao_peca = $this->situacao_peca;
        $retorno = [];

        $rastreabilidade->chunk(10000, function($result) use (&$total,&$retorno,$estabelecimentos,$situacao_peca){
            $result->each(function($query) use (&$total,&$retorno,$estabelecimentos,$situacao_peca){
                $retorno[] = [
                    'acao' => $query->acao,
                    'produto' => (!empty($query->fracaoNasajon->produto_especificacao)) ? $query->fracaoNasajon->produto_especificacao : '',
                    'codigo' => $query->produto,
                    'fracao' => $query->codigo,
                    'detentor' => (!empty($query->detentor)) ? $estabelecimentos[intval($query->detentor)] : '',
                    'proprietario' => (!empty($query->proprietario)) ? $estabelecimentos[intval($query->proprietario)] : '',
                    'documento' => $query->documento,
                    'local' => (!empty($query->fracaoNasajon->le_codigo)) ? $query->fracaoNasajon->le_codigo : '',
                    'endereco' => $query->endereco,
                    'situacao' => (isset($situacao_peca[$query->fracaoNasajon->situacao])) ? $situacao_peca[$query->fracaoNasajon->situacao] : '',
                    'usuario' => (!empty($query->nome)) ? $query->nome : '',
                    'data_criacao' => (!empty($query->data_hora_criacao)) ? parserDataEHora($query->data_hora_criacao) : '',
                    'quantidade' => ($query->quantidade > 0) ? parserQtd($query->quantidade) : '',
                ];
                
            });
        });

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'response' => $retorno,
            ]
        ];
        return response()->json($response,200);
    }

    public function modalRastreamentoFracoes(Request $request){
        $campos = $request->only(['codigo_fracao']);

        $codigo_fracao = $campos['codigo_fracao'];

        $rastreabilidade = RastreabilidadeFracoesNasajon::where('codigo','ilike',$codigo_fracao)
        ->with(['fracaoNasajon'])
        ->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $retorno = [];
        $this->carregarSitucao();
        $situacao_peca = $this->situacao_peca;

        $rastreabilidade->each(function($query) use (&$total,&$retorno,$estabelecimentos,$situacao_peca){
            $retorno[] = [
                'acao' => $query->acao,
                'produto' => (!empty($query->fracaoNasajon->produto_especificacao)) ? $query->fracaoNasajon->produto_especificacao : '',
                'codigo' => $query->produto,
                'fracao' => $query->codigo,
                'detentor' => (!empty($query->detentor)) ? $estabelecimentos[intval($query->detentor)] : '',
                'proprietario' => (!empty($query->proprietario)) ? $estabelecimentos[intval($query->proprietario)] : '',
                'documento' => $query->documento,
                'local' => (!empty($query->fracaoNasajon->le_codigo)) ? $query->fracaoNasajon->le_codigo : '',
                'endereco' => $query->endereco,
                'situacao' => (isset($situacao_peca[$query->fracaoNasajon->situacao])) ? $situacao_peca[$query->fracaoNasajon->situacao] : '',
                'usuario' => (!empty($query->nome)) ? $query->nome : '',
                'data_criacao' => (!empty($query->data_hora_criacao)) ? parserDataEHora($query->data_hora_criacao) : '',
                'quantidade' => ($query->quantidade > 0) ? parserQtd($query->quantidade) : '',
            ];
            
        });
        
        return view("programs.reastreabilidade.modal.rastreabilidade")->with(['retorno' => $retorno]);
    }

    private function carregarSitucao(){
        $situacao = [];

        $situacao = [
            0 => 'Liberado',
            1 => 'Fracionado',
            2 => 'Reservado',
            3 => 'Expedido',
            4 => 'Em importação',
        ];

        return $this->situacao_peca = $situacao;
    }
}
