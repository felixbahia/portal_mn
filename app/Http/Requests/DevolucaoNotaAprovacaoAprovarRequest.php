<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Crypt;

use App\NotasNasajon;
use App\DevolucaoNota;
use App\TransportadorNasajon;

use Illuminate\Support\Facades\DB;

use Auth;
use Carbon\Carbon;

class DevolucaoNotaAprovacaoAprovarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $fail_id = false;
        $erro_permissao = false;
        
        try {
            $id = Crypt::decrypt($this->id);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $fail_id = true;
        }
        
        if(!$fail_id){
            $devolucaoNotaObj = DevolucaoNota::with(
                    'status_detalhes',
                    'nota_nasajon',
                    'nota_nasajon.item',
                    'nota_nasajon.item.produto_detalhes'
                )
                ->find($id);
                
            $outrasDevolucoesNotaObj = DevolucaoNota::with('produtos')
                ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                ->where('id', '!=', $id)
                ->get();
        }

        if(
            (
                Auth::user()->hasPermissionTo("action App\DevolucaoNotaAprovacao " . $devolucaoNotaObj->status_detalhes->chave) == false &&
                (
                    ($devolucaoNotaObj->pedido->userPortal->responsavel != Auth::id() && $devolucaoNotaObj->pedido->userPortal->id != Auth::id()
                    ) && Auth::user()->tipo_usuario_id != 19 && $devolucaoNotaObj->devolucao_nota_status_id != 2
                )
            ) && Auth::user()->tipo_usuario_id != 1
        ){
            return $fail('O usuário não tem autorização para esta ação');
        }

        if(
            Auth::user()->hasPermissionTo("action App\DevolucaoNotaAprovacao " . $devolucaoNotaObj->status_detalhes->chave) == true &&
            $devolucaoNotaObj->devolucao_nota_status_id == 2 &&
            Carbon::now()->diffInDays($devolucaoNotaObj->nota_nasajon->emissao) > 30 &&
            !(Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::id() == 105)
        ){
            $erro_permissao = 'Somente a diretoria pode aprovar devoluções de notas emitidas a mais de 30 dias.';
        }

        if(!empty($devolucaoNotaObj)){
            $notaNasajon = $devolucaoNotaObj->nota_nasajon;
        }

        return [
            'descricao_documento' => 'array',
            'descricao_documento.*' => ['required_with:documento'],
            'documento.*' => [
                'mimetypes:application/pdf,application/msword,image/jpeg,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:2048',
                'required_with:descricao_documento'
            ],
            'id' => [
                'required',
                function ($attribute, $value, $fail) use($fail_id, $erro_permissao){
                    if($fail_id){
                        return $fail('Registro inválido!');
                    }
                    if($erro_permissao){
                        return $fail($erro_permissao);
                    }
                }
            ],
            
            'laudo_imagem' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==1?'required':''),
                "mimes:pdf"
            ],

            'nome_contato' => ($devolucaoNotaObj->devolucao_nota_status_id==2?'required':''),
            'telefone_contato' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==2?'required':''),
                'between:14,15'
            ],
            'email_contato' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==2?'required':''),
                'email'
            ],
            'responsabilidade_frete' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==2?'required':''),
                Rule::in([
                    'textil',
                    'cliente',
                    'representante'
                ])
            ],
            'transportador' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==3?'required':''),
                function($attribute, $value, $fail){
                    if(TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), $value)->doesntExist()){
                        return $fail('Transportadora não encontrada');
                    }
                }
            ],
            'transportador_email' => [
                (($devolucaoNotaObj->devolucao_nota_status_id==3 && !in_array($this->transportador, ['RETIRA -', 'ENTREGA -']))?'required' :''),
                (($devolucaoNotaObj->devolucao_nota_status_id==3 && !in_array($this->transportador, ['RETIRA -', 'ENTREGA -']))?'email' :''),
            ],
            'nota_cliente_numero' => [
                ($devolucaoNotaObj->devolucao_nota_status_id==3?'required':''),
            ],
            'nota_cliente_arquivo' => [
                'mimes:pdf,xml' 
            ],
            'romaneio_arquivo' => [
                'mimes:doc,docx,xls,xlsx',
                'max:2000' 
            ],
            'produtos.*' => [
                ($devolucaoNotaObj->devolucao_nota_status_id == 4? 'required':''),
                function($attribute, $value, $fail) use($devolucaoNotaObj, $outrasDevolucoesNotaObj){

                    $item = $devolucaoNotaObj->nota_nasajon->item->firstWhere('id_item_nota', $value['id']);

                    $devolvidos = 0;

                    $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');

                    $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                        ->pluck('itens_faturamento')
                        ->flatten()
                        ->where('Item - Código', $item->produto_detalhes->codigo_produto);
                
                    if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                        $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                    }

                    $requisicaoDevolucaoItemObj = $outrasDevolucoesNotaObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
                
                    if($requisicaoDevolucaoItemObj->isNotEmpty()){
                        $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                    }


                    if($devolucaoNotaObj->valor_parcial === true){
                        $quantidade_devolvida = $devolucaoNotaObj->produtos->where('produto_id', $value['id'])->first();
                    }
                    else{
                        $quantidade_devolvida = $item->quantidade;
                    }

                    if($item->quantidade - $devolvidos != parserNumber($value['quantidade_recebida'])){
                        return $fail('Quantidade incorreta.');
                    }
                    
                }
            ],
            'nota_remessa'=> [
                (in_array($devolucaoNotaObj->estabelecimento, [03,04]) && $devolucaoNotaObj->devolucao_nota_status_id==6?'required':''),
                function($attribute, $value, $fail) use ($devolucaoNotaObj){   
                    $notaRemessaNasajonObj = NotasNasajon::
                        where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($value, 0))
                        ->where('estabelecimento_codigo', $devolucaoNotaObj->estabelecimento)
                        ->first();
                    
                    if(empty($notaRemessaNasajonObj)){
                        return $fail('Nota não encontrada.');
                    }
                }
            ]
        ];
    }

    public function messages()
    {   
        $messages = [];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $messages['descricao_documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Descrição do Documento', 'values' => 'Anexo']);
                
                $messages['documento.'.$key.'.max'] = __('validation.max.file', ['attribute' => 'Documento']);
                $messages['documento.'.$key.'.mimetypes'] = __('validation.mimes', ['attribute' => 'Documento', 'values' => 'PDF, DOC, PNG e JPG']);
                $messages['documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Anexo', 'values' => 'Descrição Documento']);
            }
        }
         
        $messages['valor_devolvido.required_if'] = 'Digite um valor';
        $messages['motivo.required'] = 'Selecione um motivo';
        $messages['motivo.exists'] = 'Motivo inválido';
        $messages['valor_parcial.required'] = "Escolha o tipo de devolução";
        $messages['laudo_imagem.required'] = "Escolha o arquivo do laudo";
        $messages['laudo_imagem.mimes'] = "O laudo deve ser um arquivo do tipo PDF";
        $messages['nome_contato.required'] = "Digite o nome do contato";
        $messages['telefone_contato.required'] = "Digite o telefone do contato";
        $messages['telefone_contato.between'] = 'Telefone inválido';
        $messages['email_contato.required'] = "Digite o e-mail do contato";
        $messages['email_contato.email'] = "Digite um e-mail válido";
        $messages['responsabilidade_frete.required'] = "Escolha uma opção";
        $messages['responsabilidade_frete.in'] = 'Opção inválida';
        $messages['transportador.required'] = 'Escolha uma transportadora';
        $messages['transportador_email.required'] = 'Digite o e-mail da transportadora';
        $messages['transportador_email.email'] = 'Digite um e-mail válido';
        $messages['nota_cliente_numero.required'] = 'Digite o número da nota do cliente';
        $messages['nota_cliente_arquivo.mimes'] = 'A nota do cliente deve ser um arquivo PDF ou XML';
        $messages['romaneio_arquivo.mimes'] = __('validation.mimes', ['attribute' => 'Romaneio', 'mimes' => 'DOC, DOCX, XLS e XLSX']);
        $messages['romaneio_arquivo.max'] = __('validation.max.file', ['attribute' => 'Romaneio', 'max' => '2000']);
        $messages['produtos.required_without'] = 'Pelo menos um item deve ser devolvido para prosseguir.';
        $messages['nota_remessa.required'] = 'Digite o número da nota.';
        
        return $messages;
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}