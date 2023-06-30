<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\NotaEntrada;
use App\FornecedorNasajon;

use Carbon\Carbon;

class UltimaComprasController extends Controller
{
    public function dialog(Request $request){
        $fields = $request->only('codigo_produto');

        $data_atual = Carbon::now();  
        $ano_anterior = new Carbon('last year');

        $query = NotaEntrada::select();
        $query->where('codigo_produto', 'ilike', $fields['codigo_produto']);
        $query->whereBetween('data_entrada', [$ano_anterior, $data_atual]);
        $query->where('forncedor_cpf_cnpj', 'not ilike', '06.311.274%');
        $query->where('forncedor_cpf_cnpj', 'not ilike', '05.075.884%');
        $query->orderBy('data_entrada', 'desc');
        $result = $query->get();

        $fornecedor = [];
        $estabelecimentos = $this->estabelecimentos();
        $dados = [];

        foreach($result as $value){
            
            if(!empty($value->notaEntradaNasajon)){
                $nota_id = $value->notaEntradaNasajon['Identificador Documento'];
            }
            else{
                $nota_id = '';
            }

            if(empty($value->forncedor_cpf_cnpj) && !empty($value->forncedor_codigo)){
                if(empty($fornecedor[$value->forncedor_codigo])){
                    $fornecedorNasajonObj = FornecedorNasajon::where('codigo', $value->forncedor_codigo)->first();
                    $fornecedor[$value->forncedor_codigo] = [
                        'nome' => $fornecedorNasajonObj->nome,
                        'cnpj_cpf' => $fornecedorNasajonObj->cnpj_cpf
                    ];
                }             

                if(!empty($fornecedor[$value->forncedor_codigo])){
                    $dados[] = [
                        'estabelecimento' => $estabelecimentos[intval($value->estabelecimento)],
                        'data' => parserData($value->data_entrada),
                        'nf' => $value->nota,
                        'fornecedor' => $fornecedor[$value->forncedor_codigo]['nome']." - ".$fornecedor[$value->forncedor_codigo]['cnpj_cpf'],
                        'unidade' => empty($value->unidade)? '' : $value->unidade,
                        'quantidade' => parserQtd($value->quantidade),
                        'valor' => empty($value->preco_real)? '' : parserValor($value->preco_real),
                        'nota_id' => $nota_id
                    ];
                }
            }else{
                if(empty($fornecedor[$value->forncedor_cpf_cnpj])){
                    $fornecedorNasajonObj = FornecedorNasajon::where('cnpj_cpf', $value->forncedor_cpf_cnpj)->first();
                    if(!empty($fornecedorNasajonObj)){
                        $fornecedor[$value->forncedor_cpf_cnpj] = $fornecedorNasajonObj->nome;
                    } 
                }
    
                if(!empty($fornecedor[$value->forncedor_cpf_cnpj])){
                    $dados[] = [
                        'estabelecimento' => $estabelecimentos[intval($value->estabelecimento)],
                        'data' => parserData($value->data_entrada),
                        'nf' => $value->nota,
                        'fornecedor' => $fornecedor[$value->forncedor_cpf_cnpj]." - ".$value->forncedor_cpf_cnpj,
                        'unidade' => empty($value->unidade)? '' : $value->unidade,
                        'quantidade' => parserQtd($value->quantidade),
                        'valor' => empty($value->preco_real)? '' : parserValor($value->preco_real),
                        'nota_id' => $nota_id
                    ];
                }
            }
        }
        return view('programs.ultimas_compras.modal.dialog')->with(['dados' => $dados]);
    }

    public function estabelecimentos(){
        $estabelecimentos[''] = 'Selecione';
        $estabelecimentos = array_merge($estabelecimentos, returnEmpresasNasajonView());

        return $estabelecimentos;
    }
}
