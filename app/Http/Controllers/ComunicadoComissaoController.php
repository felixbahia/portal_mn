<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ComissaoDuplicatasFiltroRequest;

use App\User;
use App\ComissaoDataFechamento;
use App\ComunicadoComissoe;

use Exception;

class ComunicadoComissaoController extends Controller
{
    public function enviarEmail(){
        set_time_limit(12000);
        $data_hoje = Carbon::now();
        
        $comissao_fechamento = ComissaoDataFechamento::where(DB::raw('data_fim +1'),$data_hoje)
        ->orWhere(DB::raw('data_fim +2'),$data_hoje)
        ->orWhere(DB::raw('data_fim +3'),$data_hoje)
        ->orWhere(DB::raw('data_fim +4'),$data_hoje)
        ->first();
        
        if(empty($comissao_fechamento)){
            return;
        }
         
        $data_inicial = Carbon::parse($comissao_fechamento->data_inicio);
        $data_final = Carbon::parse($comissao_fechamento->data_fim);

        $vendedores = User::whereIn('tipo_usuario_id',[13,12,16,19,14])
            ->whereNotNull('codigo_representante')
            ->get();

        $mes = $data_hoje->format('m/Y');

        $premio = new PremiacaoController();
        $request_premio = new Request();
        $request_premio->merge([
            'mes_ano' => $data_inicial->format('m/Y'),
            'unidade_negocio' => null,
            'vendedor' => null
        ]);
        
        $unidade_negocio_premio = $premio->filtro($request_premio);
        $unidade_negocio_premio = $unidade_negocio_premio->getOriginalContent();
        $codigo_unidades_array = [];
        $premiacao_array = [];
        $vendedor_premiacao = collect();

        if(!empty($unidade_negocio_premio['response']['premiacaos'])){
            $unidades = $unidade_negocio_premio['response']['premiacaos'];

            foreach($unidades as $unidade){
                $codigo_unidades_array[] = [
                    'unidade_codigo' => $unidade['unidade_id']
                ];
            }
        }

        if(!empty($codigo_unidades_array)){
            foreach($codigo_unidades_array as $codigo){
                $request_premio->merge([
                    'mes_ano' => $data_inicial->format('m/Y'),
                    'unidade_id' => $codigo['unidade_codigo'],
                    'vendedor' => null
                ]);

                $premiacao_array[] = $premio->modalMembros($request_premio);
            }
        }
        
        if(!empty($premiacao_array)){
            foreach($premiacao_array as $premicao){
                $dados = $premicao->getData();

                if(isset($dados['gerente'])){
                    $premio_valor = (float)str_replace(",",".",str_replace(".","",$dados['gerente']['premio_a_pagar']));
                    $vendedor_premiacao->push([
                        'vendedor_codigo' => null,
                        'meta_valor' => 0,
                        'premio_valor' => $premio_valor,
                        'id_user' => $dados['gerente']['id_user']
                    ]);
                }

                foreach($dados as $dado){
                    if(!is_array($dado))
                        continue;    
                        
                    foreach($dado as $dado_premio){
                        
                        $codigo = (isset($dado_premio['vendedor'])) ? substr($dado_premio['vendedor'],0,3) : null;

                        if($codigo == null)
                            continue;
                        $pegar_valor_preio = (isset($dado_premio['premio_total'])) ? $dado_premio['premio_total'] : $dado_premio['premio_a_pagar'];
                        $premio_valor = (float)str_replace(",",".",str_replace(".","",($pegar_valor_preio)));

                        if($premio_valor <= 0 || empty($pegar_valor_preio))
                            continue;

                        $vendedor_premiacao->push([
                            'vendedor_codigo' => $codigo,
                            'meta_valor' => (float)str_replace(",",".",str_replace(".","",$dado_premio['meta_valor'])),
                            'premio_valor' => $premio_valor,
                            'id_user' => $dado_premio['id_user']
                        ]);
                    }
                }
            }
        }
        
        $request_comissao = new ComissaoDuplicatasFiltroRequest();
        $comissao_duplicata = new ComissaoDuplicatasController();
        $request_comissao->merge([
            'estabelecimento' => null,
            'data' => $data_final->format('m/Y'),
            'representantes' => null,
            'tipo' => null,
            'confirmacao' => null,
        ]);

        $comissao = $comissao_duplicata->filter($request_comissao);
        $comissao = $comissao->getOriginalContent();

        $emailControllerObj = new EmailController;
        $collect_comissao = collect();

        if(!empty($comissao['response'])){
            $collect_comissao = collect($comissao['response']['titulos']);
        }

        $vendedores->each(function($vendedor) use ($collect_comissao,$vendedor_premiacao,$mes,$emailControllerObj,$data_inicial,$data_final){
            $vendedor_comissao = $collect_comissao->where('representante_not_parse',$vendedor->id);
            $vendedor_premio = $vendedor_premiacao->where('id_user',$vendedor->id)->sum('premio_valor');
            
            if(!empty($vendedor_comissao)){
                $vendedor_comissao = $vendedor_comissao->first();
            }

            $valor_comissao = 0;

            if(!empty($vendedor_comissao)){
                $valor_comissao = (!empty($vendedor_comissao['valor_comissao'])) ? $vendedor_comissao['valor_comissao'] : 0;
            }

            if((float)str_replace(",",".",str_replace(".","",$valor_comissao)) > 0){
                $valor_comissao = (float)str_replace(",",".",str_replace(".","",$valor_comissao));
            }
            
            try{
                $link_comissao = md5($vendedor->id.$mes.$vendedor->name.'comissao');
                $verifica_envio_comissao = ComunicadoComissoe::where('link',$link_comissao)->first();
                if($vendedor->tipo_usuario_id == '14' && $vendedor_premio > 0 && empty($verifica_envio_comissao) || $vendedor->tipo_usuario_id == '13' && $vendedor_premio > 0 && empty($verifica_envio_comissao) || $vendedor->tipo_usuario_id == '19' && $vendedor_premio > 0 && empty($verifica_envio_comissao) ||  $vendedor->tipo_usuario_id == '16' && $vendedor_premio > 0 && empty($verifica_envio_comissao) || $vendedor->tipo_usuario_id == '12' && $vendedor_premio > 0 && empty($verifica_envio_comissao)){
                    $link = md5($vendedor->id.$mes.$vendedor->name.'premio');
                    $verifica_envio = ComunicadoComissoe::where('link',$link)->first();
                    
                    $comunicado_comissoes = new ComunicadoComissoe;
                    $comunicado_comissoes->tipo = 'premio';
                    $comunicado_comissoes->vendedor_codigo = $vendedor->codigo_representante;
                    $comunicado_comissoes->valor_meta = 0;
                    $comunicado_comissoes->valor_comissao = $vendedor_premio;
                    $comunicado_comissoes->created_by = 1;
                    $comunicado_comissoes->link = $link;
                    $comunicado_comissoes->confirmacao = false;
                    
                    
                    $email = "<p>Caro Colaborador ".$vendedor->name.",</p><br>";
                    $email .= "<p>Conforme nossa política de vendas, informamos que sua comissão no período de ".$data_inicial->format('01/m')." à ".$data_inicial->format('t/m')." no valor de ".parserValor($vendedor_premio)." e sua comissão no periodo de ".$data_inicial->format('d/m')." à ".$data_final->format('d/m')." no valor de ".parserValor($valor_comissao).",  foi apurada e está aguardando seu aceite para liberação.</p><br/>";
                    $email .= "Por favor enviar o PDF da NFS-E (Nota fiscal de serviço) juntamente com seu arquivo eletrônico (xml ou txt) para o email: fiscal.nf@tecidosmn.com.br";
                    $email .= "<p>Qualquer dúvida consulte seu Gestor.</p><br/>";
                    $email .= '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.route("comunicado_comissao.confirmacao",$link).'" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="10%" strokecolor="#0c299d" fillcolor="#003554"><w:anchorlock/><center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Confirmar</center></v:roundrect><![endif]--><a href="'.route("comunicado_comissao.confirmacao",$link).'"style="background-color:#003554;border:1px solid #0c299d;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">Confirmar</a></div></center>';
    
                    try{
                        if(!empty($verifica_envio) && $verifica_envio->confirmacao == false || empty($verifica_envio)){
                            $mail_envio = $emailControllerObj->sendEmailToken('00', 'comunicado_comissao', [$vendedor->email] , ['nome_vendedor' => $vendedor->name,'corpo' => $email]);
                        }
    
                        if(empty($verifica_envio)){
                            $comunicado_comissoes->save();
                        }
                    }catch(Exception $e){
                        throw new Exception($e->getMessage());
                    }
    
                }else if($valor_comissao > 0){
    
                    $link = md5($vendedor->id.$mes.$vendedor->name.'comissao');
                    $verifica_envio = ComunicadoComissoe::where('link',$link)->first();

                    $comunicado_comissoes = new ComunicadoComissoe;
                    $comunicado_comissoes->tipo = 'comissao';
                    $comunicado_comissoes->vendedor_codigo = $vendedor->codigo_representante;
                    $comunicado_comissoes->valor_meta = 0;
                    $comunicado_comissoes->valor_comissao = $valor_comissao;
                    $comunicado_comissoes->created_by = 1;
                    $comunicado_comissoes->link = $link;
                    $comunicado_comissoes->confirmacao = false;
                    
                    try{
                        if(empty($verifica_envio)){
                            $comunicado_comissoes->save();
                        }
                    }catch(\Illuminate\Database\QueryException $e){
                        throw new Exception(vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()));
                    }
    
                    $email = "<p>Caro Colaborador ".$vendedor->name.",</p><br>";
                    $email .= "<p>Conforme nossa política de vendas, informamos que sua comissão no período de ".$data_inicial->format('d/m')." à ".$data_final->format('d/m')." no valor de ".parserValor($valor_comissao).", foi apurada e está aguardando seu aceite para liberação.</p><br>";
                    $email .= "Por favor enviar o PDF da NFS-E (Nota fiscal de serviço) juntamente com seu arquivo eletrônico (xml ou txt) para o email: fiscal.nf@tecidosmn.com.br";
                    $email .= "<p>Qualquer dúvida consulte seu Gestor.</p><br/>";
                    $email .= '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.route("comunicado_comissao.confirmacao",$link).'" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="10%" strokecolor="#0c299d" fillcolor="#003554"><w:anchorlock/><center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Confirmar</center></v:roundrect><![endif]--><a href="'.route("comunicado_comissao.confirmacao",$link).'"style="background-color:#003554;border:1px solid #0c299d;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">Confirmar</a></div></center>';
    
                    try{
                        if(!empty($verifica_envio) && $verifica_envio->confirmacao == false || empty($verifica_envio)){
                            $mail_envio = $emailControllerObj->sendEmailToken('00', 'comunicado_comissao', [$vendedor->email] , ['nome_vendedor' => $vendedor->name,'corpo' => $email]);
                        }
                    }catch(Exception $e){
                        throw new Exception($e->getMessage());
                    }
                }
            }catch(Exception $e){
                return 'Erro vendedor:'.$vendedor;
            }
            
        });
    }


    public function confirmarComissao($link){
        $verificar_comunicado = ComunicadoComissoe::where('link',$link)->with('vendedor')->first();

        if(empty($verificar_comunicado)){
            return abort(404);
        }
        
        if($verificar_comunicado->tipo == 'premio'){
            $mes = Carbon::parse($verificar_comunicado->created_at)->format('m/Y');
            $link = md5($verificar_comunicado->vendedor->id.$mes.$verificar_comunicado->vendedor->name.'comissao');
    
            $verificar_comunicado_comissao = ComunicadoComissoe::with(['vendedor'])->where('link',$link)->first();

            if(isset($verificar_comunicado_comissao->id) && $verificar_comunicado_comissao->confirmacao == true){
                return view('programs.comunicado_comissao.confirmacao_realizada');
            }

            $comunicado_comissoes = new ComunicadoComissoe;
            $comunicado_comissoes->tipo = 'comissao';
            $comunicado_comissoes->vendedor_codigo = $verificar_comunicado->vendedor_codigo;
            $comunicado_comissoes->valor_meta = 0;
            $comunicado_comissoes->valor_comissao = $verificar_comunicado->valor_comissao;
            $comunicado_comissoes->created_by = (!empty($verificar_comunicado->vendedor->id)) ? $verificar_comunicado->vendedor->id : null;
            $comunicado_comissoes->updated_by = (!empty($verificar_comunicado->vendedor->id)) ? $verificar_comunicado->vendedor->id : null;
            $comunicado_comissoes->link = $link;
            $comunicado_comissoes->confirmacao = true;
            $comunicado_comissoes->data_confirmacao = Carbon::now();
            $comunicado_comissoes->save();
            
            $verificar_comunicado->updated_by = (!empty($verificar_comunicado->vendedor->id)) ? $verificar_comunicado->vendedor->id : null;
            $verificar_comunicado->confirmacao = true;
            $verificar_comunicado->data_confirmacao = Carbon::now();
            $verificar_comunicado->save();

            return view('programs.comunicado_comissao.comunidado_enviado');
        }else if($verificar_comunicado->confirmacao == false){
            $verificar_comunicado->updated_by = (!empty($verificar_comunicado->vendedor->id)) ? $verificar_comunicado->vendedor->id : null;
            $verificar_comunicado->confirmacao = true;
            $verificar_comunicado->data_confirmacao = Carbon::now();
            $verificar_comunicado->save();

            return view('programs.comunicado_comissao.comunidado_enviado');
        }else if($verificar_comunicado->confirmacao == true){
            return view('programs.comunicado_comissao.confirmacao_realizada');
        }
    }

}
