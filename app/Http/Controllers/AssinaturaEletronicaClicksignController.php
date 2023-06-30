<?php

namespace App\Http\Controllers;

use App\ClicksignDocumento;
use App\ClicksignErro;
use App\ClicksignSignatario;
use App\ClicksignSignatarioDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class AssinaturaEletronicaClicksignController extends Controller
{
    private function header(){
        if(config('app.debug') == true){
            return [
                'Host' => 'sandbox.clicksign.com',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
             ];
        }else{
            return [
                'Host' => 'app.clicksign.com',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
             ];
        }
    }
    
    private function setUrlRequest($acao){
		if(config('app.debug') == true){
			$urlSandbox = config('clicksign.url.sandbox');
            $url = $urlSandbox.$acao.'?access_token=24f5a3ca-3135-421f-8ffc-53957d27b198';
		}else{
			$urlProducao = config('clicksign.url.producao');
            $url = $urlProducao.$acao.'?access_token=22ae70af-8e63-4da5-88a0-9a644b807290';
		}
       
        return $url;
    }
    
    private function enviarDocumento($path_pdf, $tipo){
        $pdf = Storage::exists($path_pdf);
        if($pdf == false){
            throw new \Exception('Arquivo não encontrado!');
        }

        $extensao_arquivo = substr($path_pdf, -4);
        if($extensao_arquivo != '.pdf'){
            throw new \Exception('O arquivo somente deve ser PDF!');
        }

        $nome_arquivo = basename($path_pdf);

        if($tipo === 'pedido'){
            $caminho = '/Pedido/'.$nome_arquivo;
            $tempo_de_espera = 3;
        }else{
            $caminho = '/Renegociacao/'.$nome_arquivo;
            $tempo_de_espera = 2;
        }

        $body = [
            'document' => [
                'path' => $caminho,
                'content_base64' => 'data:application/pdf;base64,'.base64_encode(Storage::get($path_pdf)),
                'deadline_at' => Carbon::now()->addDays($tempo_de_espera)->format('Y-m-d\TH:i:s.uP'),
                'auto_close' => true,
                'locale' => 'pt-BR',
                'sequence_enabled' => false
            ]
        ];

        $body = json_encode($body);

        $client = new Client;
        $request = $client->request(
            'POST',
            $this->setUrlRequest('documents'), 
            [
                'headers' => $this->header(),
                'body' => $body,
                'http_errors' => false
            ]
        );

        if($request->getStatusCode() == 201){
            $json_retorno = $request->getBody()->getContents();
            $array = json_decode($json_retorno, true);
            $array['document']['json_retorno'] = $json_retorno;
            $array['document']['caminho_arquivo'] = $path_pdf;
            $ultimo_id = $this->salvarDocumento($array);
        }else{
            $json_retorno = $request->getBody()->getContents();
            $mensagem_erro = $this->tratarCodigoHttpRequest($request);
            $array = json_decode($body, true);
            $array['document']['mensagem_erro'] = $mensagem_erro;
            $array['document']['json_retorno_erro'] = $json_retorno;
            $array['document']['json_enviado'] = $body;
            $array['document']['caminho_arquivo'] = $path_pdf;
            $this->salvarDocumento($array);
            throw new \Exception($mensagem_erro);
        }

        $documento = [
            'id_portal' => $ultimo_id,
            'id_clicksign' => $array['document']['key']
        ];

        return $documento;
    }

    private function salvarDocumento($array){

        $ClicksignDocumentoObj = new ClicksignDocumento;

        if(!isset($array['document']['json_retorno_erro'])){
            $ClicksignDocumentoObj->documento_clicksign_id = $array['document']['key'];
            $ClicksignDocumentoObj->caminho_arquivo = $array['document']['caminho_arquivo'];
            $ClicksignDocumentoObj->status = $array['document']['status'];
            $ClicksignDocumentoObj->json_retorno = $array['document']['json_retorno'];
            $ClicksignDocumentoObj->save();
            $ultimo_id = $ClicksignDocumentoObj->id;
        }else{
            $ClicksignDocumentoObj->caminho_arquivo = $array['document']['caminho_arquivo'];
            $ClicksignDocumentoObj->save();
            $ClicksignErroObj = new ClicksignErro;
            $ClicksignErroObj->clicksign_documentos_id = $ClicksignDocumentoObj->id;
            $ClicksignErroObj->json_retorno_erro = $array['document']['json_retorno_erro'];
            $ClicksignErroObj->mensagem_erro = $array['document']['mensagem_erro'];
            $ClicksignErroObj->json_enviado = $array['document']['json_enviado'];
            $ClicksignErroObj->save();
        }
        return isset($ultimo_id) ? $ultimo_id : '';
    }

    private function criarSignatario($signatarios){
        if(!is_array($signatarios)){
            throw new \Exception('A variável dever ser uma array!');
        }

        $signatarios_id = [];

        foreach($signatarios as $usuario){
            $body = [
                'signer' => [
                    'email' => $usuario['email'],
                    'auths' => [
                        'email'
                    ],
                    'name' => $usuario['nome'],
                    'documentation' => !empty($usuario['cpf']) && isset($usuario['cpf']) ? $usuario['cpf'] : null,
                    'birthday' => !empty($usuario['data_nascimento']) && isset($usuario['data_nascimento']) ? $usuario['data_nascimento'] : null,
                    'has_documentation' => true,
                    'selfie_enabled' => false,
                    'handwritten_enabled' => false,
                    'official_document_enabled' => false,
                    'liveness_enabled' => false
                ]
            ];  

            $body = json_encode($body);

            $client = new Client;
            $request = $client->request(
                'POST',
                $this->setUrlRequest('signers'), 
                [
                    'headers' => $this->header(),
                    'body' => $body,
                    'http_errors' => false
                ]
            );

            if($request->getStatusCode() == 201){
                $json_retorno = $request->getBody()->getContents();
                $array = json_decode($json_retorno, true);
                $array['signer']['json_retorno'] = $json_retorno;
                $this->salvarSignatario($array);
            }else{
                $json_retorno = $request->getBody()->getContents();
                $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                $array = json_decode($body, true);
                $array['signer']['mensagem_erro'] = $mensagem_erro;
                $array['signer']['json_retorno_erro'] = $json_retorno;
                $array['signer']['json_enviado'] = $body;
                $this->salvarSignatario($array);
                throw new \Exception($mensagem_erro);
            }
            
            
            $signatarios_id[] = [
                'tipo' => $usuario['tipo'],
                'id' => $array['signer']['key']
            ];
        }

        if(config('app.debug') == true){
            $signatarios_id['802bfabf-9db5-4945-a7c1-b0699adea118']['tipo'] = 'representante_legal';
            $signatarios_id['802bfabf-9db5-4945-a7c1-b0699adea118']['id'] = '802bfabf-9db5-4945-a7c1-b0699adea118';
        }else{
            $signatarios_id['8a86e15e-e8a9-4835-b03f-590fec544a89']['tipo'] = 'representante_legal';
            $signatarios_id['8a86e15e-e8a9-4835-b03f-590fec544a89']['id'] = '8a86e15e-e8a9-4835-b03f-590fec544a89';
        }

        return $signatarios_id;
    }

    private function salvarSignatario($array){

        $ClicksignSignatarioObj = new ClicksignSignatario;

        if(!isset($array['signer']['json_retorno_erro'])){
            $ClicksignSignatarioObj->signatario_clicksign_id = $array['signer']['key'];
            $ClicksignSignatarioObj->nome = $array['signer']['name'];
            $ClicksignSignatarioObj->email = $array['signer']['email'];
            $ClicksignSignatarioObj->cpf = $array['signer']['documentation'];
            $ClicksignSignatarioObj->data_nascimento = $array['signer']['birthday'];
            $ClicksignSignatarioObj->json_retorno = $array['signer']['json_retorno'];
            $ClicksignSignatarioObj->save();
        }else{
            $ClicksignSignatarioObj->nome = isset($array['signer']['name']) ? $array['signer']['name'] : '';
            $ClicksignSignatarioObj->email = isset($array['signer']['email']) ? $array['signer']['email'] : '';
            $ClicksignSignatarioObj->cpf = $array['signer']['documentation'];
            $ClicksignSignatarioObj->data_nascimento = $array['signer']['birthday'];
            $ClicksignSignatarioObj->save();
            $ClicksignErroObj = new ClicksignErro;
            $ClicksignErroObj->clicksign_signatarios_id = $ClicksignSignatarioObj->id;
            $ClicksignErroObj->json_retorno_erro = $array['signer']['json_retorno_erro'];
            $ClicksignErroObj->mensagem_erro = $array['signer']['mensagem_erro'];
            $ClicksignErroObj->json_enviado = $array['signer']['json_enviado'];
            $ClicksignErroObj->save();
        }
    }

     private function adicionarSignatarioDocumento($documento, $signatarios_id){
        $request_signature_key = [];

        foreach($signatarios_id as  $signatario){
            if($signatario['id'] != '802bfabf-9db5-4945-a7c1-b0699adea118' && $signatario['id'] != '8a86e15e-e8a9-4835-b03f-590fec544a89'){
                $ClicksignSignatario = ClicksignSignatario::where('signatario_clicksign_id',$signatario['id'])
                ->first();
            }
           
            switch($signatario['tipo']){
                case 'fiador':
                    $sign_as = 'surety';
                break;
                case 'venia_conjugal':
                    $sign_as = 'intervening';
                break;
                case 'representante_legal':
                    $sign_as = 'legal_representative';
                break;
            }

            $bodys[] = [
                'list' => [
                    'document_key' => $documento['id_clicksign'],
                    'signer_key' => $signatario['id'],
                    'sign_as' => $sign_as,
                    'message' => isset($ClicksignSignatario->nome) ? "Prezado(a) ".$ClicksignSignatario->nome.",\nPor favor assine o documento.\n\nQualquer dúvida estou à disposição.\n\nAtenciosamente,\nMN  Tecidos" : ''
                ]
            ]; 
        } 

        foreach($bodys as $value){
            $body = json_encode($value);

            $client = new Client;
            $request = $client->request(
                'POST',
                $this->setUrlRequest('lists'), 
                [
                    'headers' => $this->header(),
                    'body' => $body,
                    'http_errors' => false
                ]
            );

            if($request->getStatusCode() == 201){
                $json_retorno = $request->getBody()->getContents();
                $array = json_decode($json_retorno, true);
                $array['list']['json_retorno'] = $json_retorno;
                $this->salvarSignatarioDocumento($array);
            }else{
                $json_retorno = $request->getBody()->getContents();
                $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                $array = json_decode($body, true);
                $array['list']['mensagem_erro'] = $mensagem_erro;
                $array['list']['json_retorno_erro'] = $json_retorno;
                $array['list']['json_enviado'] = $body;
                $this->salvarSignatarioDocumento($array);
                throw new \Exception($mensagem_erro);
            }

            $signatario_id = json_decode($body, true);

            if($signatario_id['list']['signer_key'] == '802bfabf-9db5-4945-a7c1-b0699adea118'){
                $request_signature_key_MN = $array['list']['request_signature_key'];
            }
            elseif($signatario_id['list']['signer_key'] == '8a86e15e-e8a9-4835-b03f-590fec544a89'){
                $request_signature_key_MN = $array['list']['request_signature_key'];
            }else{
                $request_signature_key_cliente = $array['list']['request_signature_key'];
            }

            $request_signature_key[] = [
                'request_signature_key' => isset($request_signature_key_cliente) ? $request_signature_key_cliente : '',
                'request_signature_key_MN' => isset($request_signature_key_MN) ? $request_signature_key_MN : ''
            ];
            
        }
        return $request_signature_key;
    }

    private function salvarSignatarioDocumento($array){
        $ClicksignSignatarioDocumentoObj = new ClicksignSignatarioDocumento;

        $ClicksignDocumento =  ClicksignDocumento::where('documento_clicksign_id', $array['list']['document_key'])->first();
        $ClicksignSignatario =  ClicksignSignatario::where('signatario_clicksign_id', $array['list']['signer_key'])->first();

        if(!isset($array['list']['json_retorno_erro'])){
            $ClicksignSignatarioDocumentoObj->signatario_documento_clicksign_id = $array['list']['key'];
            $ClicksignSignatarioDocumentoObj->request_signature_id = $array['list']['request_signature_key'];
            $ClicksignSignatarioDocumentoObj->json_retorno = $array['list']['json_retorno'];
            $ClicksignSignatarioDocumentoObj->clicksign_documentos_id = $ClicksignDocumento->id;
            $ClicksignSignatarioDocumentoObj->clicksign_signatarios_id = $ClicksignSignatario->id;
            $ClicksignSignatarioDocumentoObj->save();
        }else{
            $ClicksignSignatarioDocumentoObj->clicksign_documentos_id = $ClicksignDocumento->id;
            $ClicksignSignatarioDocumentoObj->clicksign_signatarios_id = $ClicksignSignatario->id;
            $ClicksignSignatarioDocumentoObj->save();
            $ClicksignErroObj = new ClicksignErro;
            $ClicksignErroObj->clicksign_signatario_documentos_id = $ClicksignSignatarioDocumentoObj->id;
            $ClicksignErroObj->json_retorno_erro = $array['list']['json_retorno_erro'];
            $ClicksignErroObj->mensagem_erro = $array['list']['mensagem_erro'];
            $ClicksignErroObj->json_enviado = $array['list']['json_enviado'];
            $ClicksignErroObj->save();
        }
    }

    private function solicitarAssinaturaAPI($adicionarSignatarioDocumento){
        foreach($adicionarSignatarioDocumento as $key => $signature){
            
            if(config('app.debug') == true){
                if(!empty($adicionarSignatarioDocumento[$key]['request_signature_key_MN'])){
                    $body = [
                        'request_signature_key' => $adicionarSignatarioDocumento[$key]['request_signature_key_MN'],
                        'secret_hmac_sha256' => hash_hmac('sha256', $adicionarSignatarioDocumento[$key]['request_signature_key_MN'], '-----BEGIN RSA PRIVATE KEY----- MIIEpgIBAAKCAQEAtapdLCQPCKU4aDMElKkFvM3aqD8N9gs4D8it/Xx9iDyFvCxL oxLt/QGHWHxvjEl6qyEFRockzrHow4ycdZLlEnBOTwOAX7zt9CPDbbm18WnX9TtL e/TWf+0rzdeKzySotwt5tUolC7CCG7HKsx4gpvEn2AR4VbvtOKuaFRiYMX/UfaD+ OX69/NgFDYU061XmlkNJuRLpdEXIDOQPjC8U6UeKGGOr+RDsXlI/rd7s9ZvEFp8I 0expIFgksCjNjbFuKyK7kr6iBNG38Xo+zuGZpGiVHQWyy16q/prSHiGRWsfdMuMq UlFvZWiCl+EG5EFh8XGicLrvg23BYCot0DC3JQIDAQABAoIBAQChpX0QiOljrPhb J4Jc8WUskhONf8XheCwjR1MiakKdFhV90gBfk7l2VoTjRJ5ROxTO7yvtjNVAomfW kOiradExLgNQJXJ2PfuSMLx1hzkRHjhOert5CexPmm9O0wsttJX3gluC5/28wAuV rwcJiLZPNehZO+kDiJvVwEPzn5XU/ESs07RwV7Llpwq5GFm/e56uhma1XGSDtQ8r TXy4iBdvLaf9hEZg6yylPLTfgGoUy1NsTRAYwDN7XiwRH7wbM/juS9vpkT2R/0E/ S8nCYd1a+gIp/gF/CDZIkj/Gypy97BS0Kf1/kKxKJRprFtujnV87aa279cyh8jtC 14MMdsfhAoGBAODuWCXLRqTxnU55QCGvleaNM2xKkXVpXr8MXw7ynBBPoBaYR4eq UMvW3r8zqa+qMjW1e8Ep2CaaMaMiOMF/vmgKI0RUW5nvaroRtT+RWJ590YFdIx4F V8Q1TlQiT1N0LqMsiiP6vQTS2X9tz95efmTisfWa7TmjYgMia5Y21PWNAoGBAM7C IbZVZdKPyMC4KEg5qKUWkKpEkEq4M7poOrBr98j5hp40gmkcR/wbt0GByuPrLI1t r8GwcfC9uo0GKoiMpJjM5Wo3gvInnfLLu88B2dhZEG/NbEYymJtjziA38b8C417E gjQ5M1ZqJb0l7RQW7/N4jSRKreTUymb1uVVgcqX5AoGBAKhDZImRUV2eqXDE36bT dS2tP2SpO7s7gfclSA8kin7hMf71F71zOVHjgWpDOZMBnOH0y4kqxlnKS4uf1Blc eJHX054QBR5YrdxX1uCg2ExoDsvZYqXYlVlgDyJ9MB5b8W97qDNWJQRwvufGvBO7 WI4bz8jNhtzxTibOHvGWPyUZAoGBAMbUifb+SN0kyLoMXzCVwkiAWr5jER/J5HTu pwHh0nfC/mJFvOO3/sHDJYgpeSOVlVKsmMh7FhedXTkhJOYL2n3XDQgIOjUFRC+p HQMFlKkpfVUHB4i6P2evoxn4stItxNntwAjYuTXw/jnXrxYSuM46sACmkHPu7nX0 +DXSem6BAoGBAOC6y32dP5WGfF/2qUqNGTVkEydzowHRMysiUKlv+Vl0Nj3Q9mJy +XdRvq98VPHJUTz3Vp9pAd0B4jazVFyJMppjQKAacqhsP6VhsJLuqZA0WMfMVFBL z3B75QGoGtT5APVeanaeIBvuADNYCz61lWliI5mefJRH0CpmVF0oEeA3 -----END RSA PRIVATE KEY-----')
                    ];

                    $body = json_encode($body);

                    $client = new Client;
                    $request = $client->request(
                        'POST',
                        $this->setUrlRequest('sign'), 
                        [
                            'headers' => $this->header(),
                            'body' => $body,
                            'http_errors' => false
                        ]
                    );
            
                    if($request->getStatusCode() != 200){
                        $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                        $json_retorno = $request->getBody()->getContents();
                        $ClicksignErroObj = new ClicksignErro;
                        $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                        $ClicksignErroObj->json_enviado = $body;
                        $ClicksignErroObj->json_retorno_erro = !empty($json_retorno) ? $json_retorno : null;
                        $ClicksignErroObj->save();
                        throw new \Exception($mensagem_erro);
                    }
                }
            }else{
                if(!empty($adicionarSignatarioDocumento[$key]['request_signature_key_MN'])){
                    $body = [
                        'request_signature_key' => $adicionarSignatarioDocumento[$key]['request_signature_key_MN'],
                        'secret_hmac_sha256' => hash_hmac('sha256', $adicionarSignatarioDocumento[$key]['request_signature_key_MN'], '-----BEGIN RSA PRIVATE KEY----- MIIEowIBAAKCAQEAvpBz4zaUpGOfZ4lkrUdQ3BL7fsaa5/3G0kb7nerPLK/AyCe1 38SsAEAwL+0gyj3p+lFAVge57SHNxiHn0a6AsLwDZE7iJUcZDpxtwrai8eicOb0v HTpNHdUkm/RTX4kvmQR1ukZak5uPsy+ODZIVcyF21Ogigq73He3ufLTKExJP2AvV SfxYCyHaWvGverV8hcMDbKyVakccDg/LwHWQrsCIFAqv+zUNhQGgPFZbW4lFvy9h RnwmzUxhHbiHxAnnt/wuJ8iLFF4a+fYTMfzc61a0cMRHPFX0hMiT754K+AZgeCRc 0iQ6JddT2PDhy216NlWb7/yJSSD4HwFrgYHapwIDAQABAoIBAGGW5GIln3WYNxf0 uoQqk3RAnF7OIqJPyjQBefXjO/msf9OA0pknxNXUCVlh3Hr1vg8c8Q+doU+ZEiI3 VtqAvRLFFg+WfiWNBX4ACg2/Li8oamj/Q1N6eeefMcJUGyY/wQphyFB+mwAgmDSx U9QLTcTKDGr3/kRiidbf9FQU2a9tvQc2C/7DlMTaq7nV/25XqJYQlWMH62H1Y04x lpYaBnZC3FisYJ+6gcuF/nOPtNv7/ownyRzcbQDZl+HaLdLmxlDaKFK/SKAbnJzR xaATM2CFyPh2XycS+Xqjau7aI50SM5h4liHvNUrawXCLVmhwovZJ198U9E7PTj/v Jkok7HECgYEA4OIIqQAt7DJmaEGr7Mjz8k8g00NeqLeKMPpad8CHBpTfDnPm3yb5 3aA+6ckY1GhHJr7rlwfjjiVUnde4RweKqDw1lbno4ZA2KCUg54aLMv7sUV9fUjNn 1GGKmwkWDjLki5PhkBBF1AMPwOhyAmOObyBsAgo5gHR4BNdKEmFKJI0CgYEA2O7C BgGm2jsiK7jQpptOnUETFPbIsk05FlBXbO3pGrBpE0QFyIeA4IzgSwKX+RAF+v9N GfLjJnXJ1q62lldRq8TQlCaai4N7pi9qgHXI3hVpwAg7HVvnLh1Q8ByC/s4WpPRH FPzGURFXpKvm0LtXLwkPtTKQ9LIIhFlbILtyYQMCgYAyKPi5iXq0xoElHBDXHfOb xp6RwOMqStYeGpl3QJBdnXMrbBrBB5aQIqAl0V8icaf9MnSmGXJeUGFRW76UyU/a OqPRI8iYF2ydA7sVKWN/GnFnrRg7449zRdZ+wkYTILLtlyymz5pjsdMfujlD4yTw EJFG2zT8O/5RRhwVgXHiOQKBgAJlgNc4JXO7u4DoJcXxaUjrcx6EK2ts1vicIpsd dbnJwR9pXPb+KDpS1BeAC9XAi5BeSafDuyatnnE0tOesR0aygethEcwAw9juJSsO Ig3yBp0Mejq0zTUBNeexKPFPrYhc+nwM3tP+cQ1sqLwdlbT7UKUbzeYmIVn08sDF 0VmpAoGBAMuqk0gONK3o/dKhl8kbOjgOMOUEQ7YAIvMNZNFaVpWOMP4oqqcJNHJp G3N0/7UQueeLbSB6gHqG9ibmatM6NAurgKScZF9nSmXTKHAfT4HovGCuz3yXgCEP naOxxUQWbv8cVUDn7yntVMM7WtEwDNDp4L8LgvKg5ioZ3ZqDe3DB -----END RSA PRIVATE KEY-----')
                    ];

                    $body = json_encode($body);

                    $client = new Client;
                    $request = $client->request(
                        'POST',
                        $this->setUrlRequest('sign'), 
                        [
                            'headers' => $this->header(),
                            'body' => $body,
                            'http_errors' => false
                        ]
                    );
            
                    if($request->getStatusCode() != 200){
                        $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                        $json_retorno = $request->getBody()->getContents();
                        $ClicksignErroObj = new ClicksignErro;
                        $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                        $ClicksignErroObj->json_enviado = $body;
                        $ClicksignErroObj->json_retorno_erro = !empty($json_retorno) ? $json_retorno : null;
                        $ClicksignErroObj->save();
                        throw new \Exception($mensagem_erro);
                    }
                }
            }
        }
    }

    public function solicitarAssinaturaEmail($signatarios, $path_pdf, $tipo = ''){

       try {
        $documento = $this->enviarDocumento($path_pdf, $tipo);
        }catch (\Exception $e) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, tente novamente!',
                "error" => $e->getMessage(),
                "response" => []
            ];
            return response()->json($response, 422);
        }

        try {
            $signatarios_id = $this->criarSignatario($signatarios);
        }catch (\Exception $e) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, tente novamente!',
                "error" => $e->getMessage(),
                "response" => []
            ];
            return response()->json($response, 422);
        }

        try {
            $adicionarSignatarioDocumento = $this->adicionarSignatarioDocumento($documento, $signatarios_id);
        }catch (\Exception $e) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, tente novamente!',
                "error" => $e->getMessage(),
                "response" => []
            ];
            return response()->json($response, 422);
        }

        try {
            $this->solicitarAssinaturaAPI($adicionarSignatarioDocumento);
        }catch (\Exception $e) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, tente novamente!',
                "error" => $e->getMessage(),
                "response" => []
            ];
            return response()->json($response, 422);
        }

        foreach($adicionarSignatarioDocumento as $key => $signature){
            if(!empty($adicionarSignatarioDocumento[$key]['request_signature_key'])){
                $ClicksignSignatarioDocumento = ClicksignSignatarioDocumento::with('clicksignSignatario')
                ->where('request_signature_id',$adicionarSignatarioDocumento[$key]['request_signature_key'])
                ->first();
    
                $bodys[] = [
                    'request_signature_key' => $adicionarSignatarioDocumento[$key]['request_signature_key'],
                    'message' => "Prezado(a) ".$ClicksignSignatarioDocumento->clicksignSignatario->nome.",\nPor favor assine o documento. \n\nQualquer dúvida estou à disposição. \n\n Atenciosamente, \nMN Tecidos"
                ]; 
            }
        }

        if(isset($bodys)){
            foreach($bodys as $value){
                $body = json_encode($value);
    
                $client = new Client;
                $request = $client->request(
                    'POST',
                    $this->setUrlRequest('notifications'), 
                    [
                        'headers' => $this->header(),
                        'body' => $body,
                        'http_errors' => false
                    ]
                );
    
                try {
                    if($request->getStatusCode() != 202){
                        $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                        $json_retorno = $request->getBody()->getContents();
                        $ClicksignErroObj = new ClicksignErro;
                        $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                        $ClicksignErroObj->json_enviado = $body;
                        $ClicksignErroObj->json_retorno_erro = $json_retorno;
                        $ClicksignErroObj->save();
                        throw new \Exception($mensagem_erro);
                    }
                }catch (\Exception $e) {
                    $response = [
                        "status" => 'error',
                        "message" => 'Ocorreu uma instabilidade, tente novamente!',
                        "error" => $e->getMessage(),
                        "response" => []
                    ];
                    return response()->json($response, 422);
                }
            }
        }


        $response = [
            "status" => 'success',
            "message" => 'Assinatura solicitada com sucesso!',
            "error" => [],
            "response" => [
                'id' => encrypt($documento['id_portal'])
            ]
        ];
        return response()->json($response, 200);
    }


    private function tratarCodigoHttpRequest($request){
		switch($request->getStatusCode()){
			case 400:
				return 'Status Code 400 - O servidor não processará a solicitação devido a algo que é percebido como sendo um erro do cliente. Este é um erro genérico.';
			break;
			case 401:
				return 'Status Code 401 - O servidor não autorizou a requisição. Access Token inválido. ';
			break;
			case 403:
				return 'Status Code 403 - O servidor não autorizou a requisição. O Access Token não possui permissão para acessar o recurso.';
			break;
			case 404:
				return 'Status Code 404 - O servidor não encontrou o recurso ou não está disposto a divulgar sua existência.';
			break;
			case 422:
				return 'Status Code 422 - O servidor não conseguiu processar as informações contidas na requisição.';
			break;
            case 429:
				return 'Status Code 429 - Limite de envio exedido';
			break;
            case 500:
				return 'Status Code 500 - Ocorreu um erro interno inesperado.';
			break;
		}
	}

    public function atualizarDocumento(){
        $ClicksignDocumentoObj = ClicksignDocumento::where('status', 'running')->get();

        $documentos = [];
        
        foreach($ClicksignDocumentoObj as $documento){
            $request = '';
            try {
                $client = new Client;
                $request = $client->request(
                    'GET',
                    $this->setUrlRequest('documents/'.$documento->documento_clicksign_id)
                );

            }catch (\Exception $e) {
                $ClicksignErroObj = new ClicksignErro;
                $ClicksignErroObj->mensagem_erro = $e->getMessage();
                $ClicksignErroObj->clicksign_documentos_id = $documento->id;
                $ClicksignErroObj->save();

                $documento->status = 'running_bug';
                $documento->save();
            }
            
            if(!empty($request)){
                if($request->getStatusCode() != 200){
                    $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                    $json_retorno = $request->getBody()->getContents();
                    $ClicksignErroObj = new ClicksignErro;
                    $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                    $ClicksignErroObj->json_retorno_erro = $json_retorno;
                    $ClicksignErroObj->clicksign_documentos_id = $documento->id;
                    $ClicksignErroObj->save();
                }
    
                $retorno = json_decode($request->getBody()->getContents(), true);
    
                foreach($retorno as $documento){
                    $documentos[]  = [
                        'documento_clicksign_id' => $documento['key'],
                        'data_finalizacao' => !empty($documento['finished_at']) ? Carbon::createFromFormat('Y-m-d\TH:i:s.uP', $documento['finished_at'])->format('Y-m-d') : null,
                        'status' => $documento['status']
                    ];
                }
            }
        }

        $this->atualizarDataAssinatura($documentos);
        $this->atualizarBaseClicksignDocumento($documentos);
    }

    private function atualizarBaseClicksignDocumento($documentos){
        foreach($documentos as $documento){
            $ClicksignDocumentoObj = ClicksignDocumento::where('documento_clicksign_id', $documento['documento_clicksign_id'])->first();
            $ClicksignDocumentoObj->data_finalizacao = $documento['data_finalizacao'];
            $ClicksignDocumentoObj->status = $documento['status'];
            $ClicksignDocumentoObj->save();
        }
    }

    private function atualizarDataAssinatura($documentos){
        $signatarios = [];

        foreach($documentos as $documento){
            $client = new Client;
                $request = $client->request(
                'GET',
                $this->setUrlRequest('documents/'.$documento['documento_clicksign_id'])
            );

            if($request->getStatusCode() != 200){
                $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                $json_retorno = $request->getBody()->getContents();
                $ClicksignErroObj = new ClicksignErro;
                $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                $ClicksignErroObj->json_retorno_erro = $json_retorno;
                $ClicksignErroObj->save();
                throw new \Exception($mensagem_erro);
            }

            $retorno = json_decode($request->getBody()->getContents(), true);

            foreach($retorno as $value){
                foreach($value['signers'] as $signatario){
                    if(isset($signatario['signature'])){
                        $signatarios[] = [
                            'request_signature_key' => $signatario['request_signature_key'],
                            'data_assinatura' => Carbon::createFromFormat('Y-m-d\TH:i:s.uP', $signatario['signature']['signed_at'])->format('Y-m-d'),
                            'ip' => $signatario['signature']['ip_address'],
                            'link' => count($value['downloads']) > 1 ? $value['downloads']['signed_file_url'] : '',
                            'assinou_como' => $signatario['sign_as']
                        ];
                    }
                }
            }
        }
       $this->atualizarBaseClicksignSignatarioDocumento($signatarios);
    }

    private function atualizarBaseClicksignSignatarioDocumento($signatarios){
        foreach($signatarios as $signatario){
            $ClicksignSignatarioDocumentoObj = ClicksignSignatarioDocumento::with('clicksignDocumento')
            ->where('request_signature_id', $signatario['request_signature_key'])->first();

            $caminho_arquivo = Storage::path($ClicksignSignatarioDocumentoObj->clicksignDocumento->caminho_arquivo);
            $caminho_arquivo = dirname($caminho_arquivo);
            //$caminho_arquivo = chmod($caminho_arquivo, 0755);

            if(!empty($signatario['link'])){
                
                $path = public_path(substr_replace($ClicksignSignatarioDocumentoObj->clicksignDocumento->caminho_arquivo, '_assinado.pdf', -4));
                
                if(Storage::exists($path)){
                    copy($signatario['link'], Storage::path(substr_replace($ClicksignSignatarioDocumentoObj->clicksignDocumento->caminho_arquivo, '_assinado.pdf', -4)));
                }
            }
            
            $ClicksignSignatarioDocumentoObj->data_assinatura = $signatario['data_assinatura'];
            $ClicksignSignatarioDocumentoObj->ip = $signatario['ip'];
            $ClicksignSignatarioDocumentoObj->assinou_como = $signatario['assinou_como'];
            $ClicksignSignatarioDocumentoObj->save();
        }
    }

    public function cancelarDocumento($documento_id){
        $ClicksignDocumento = ClicksignDocumento::where('documento_clicksign_id', $documento_id)->first();

        if($ClicksignDocumento->status == 'running'){
            $client = new Client;
            $request = $client->request(
                'PATCH',
                $this->setUrlRequest('documents/'.$documento_id.'/cancel')
            );

            if($request->getStatusCode() != 200){
                $mensagem_erro = $this->tratarCodigoHttpRequest($request);
                $json_retorno = $request->getBody()->getContents();
                $ClicksignErroObj = new ClicksignErro;
                $ClicksignErroObj->mensagem_erro = $mensagem_erro;
                $ClicksignErroObj->json_retorno_erro = $json_retorno;
                $ClicksignErroObj->save();
                throw new \Exception($mensagem_erro);
            }
        }
    }
}
