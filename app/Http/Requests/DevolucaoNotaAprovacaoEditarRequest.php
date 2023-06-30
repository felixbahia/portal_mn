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

class DevolucaoNotaAprovacaoEditarRequest extends FormRequest
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

        if(!in_array(Auth::id(), [863, 576])  && Auth::id() != 9334 && Auth::user()->tipo_usuario_id != 1){
            $erro_permissao = 'O usuário não tem autorização para esta ação';
        }

        if(!empty($devolucaoNotaObj)){
            $notaNasajon = $devolucaoNotaObj->nota_nasajon;
        }

        return [
            'descricao_documento' => 'array',
            'descricao_documento.*' => ['required_with:documento'],
            'documento.*' => [
                'max:2048',
                'required_with:descricao_documento'
            ],
            'id' => [
                'required',
                function ($attribute, $value, $fail) use($fail_id, $erro_permissao){
                    if($fail_id){
                        return $fail(__('validation.exists', ['attribute' => 'Processo de Devolução']));
                    }
                    if($erro_permissao){
                        return $fail($erro_permissao);
                    }
                }
            ],
            
            'laudo_imagem' => [
                "mimes:pdf",
                'max:2000'
            ],

            'nome_contato' => ($devolucaoNotaObj->devolucao_nota_status_id==2?'required':''),
            'telefone_contato' => [
                'between:14,15'
            ],
            'email_contato' => [
                'email',
                'max:250'
            ],
            'responsabilidade_frete' => [
                Rule::in([
                    'textil',
                    'cliente',
                    'representante'
                ])
            ],
            'transportador' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($value) && TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), $value)->doesntExist()){
                        return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                    }
                }
            ],
            'transportador_email' => [
                'max:250'
            ],
            'nota_cliente_numero' => [
                'max:250'
            ],
            'nota_cliente_arquivo' => [
                'mimes:pdf,xml',
                'max:2000'
            ],
            'romaneio_arquivo' => [
                'mimes:doc,docx,xls,xlsx',
                'max:2000'
            ],
            'produtos.*' => [
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

                    if($item->quantidade - $devolvidos < parserNumber($value['quantidade_recebida'])){
                        return $fail(__('validation.lt.numeric', ['attribute' => 'quantidade devolvida', 'size' => $item->quantidade - $devolvidos]));
                    }
                    
                }
            ],
            'nota_remessa'=> [
                'max:250',
                function($attribute, $value, $fail) use ($devolucaoNotaObj){   
                    $notaRemessaNasajonObj = NotasNasajon::
                        where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($value, 0))
                        ->where('estabelecimento_codigo', $devolucaoNotaObj->estabelecimento)
                        ->first();
                    
                    if(!empty($value) && empty($notaRemessaNasajonObj)){
                        return $fail(__('validation.exists', ['attribute' => 'Nota de Remessa']));
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

        $messages['laudo_imagem.mimes'] = __('validation.mimes', ['attribute' => 'Laudo', 'values' => 'pdf']);
        $messages['laudo_imagem.max'] = __('validation.max.file', ['attribute' => 'Laudo', 'max' => '2000']);
        $messages['telefone_contato.between'] = __('validation.string.', ['attribute' => 'Telefone', 'min' => '14', 'max' => '15']);
        $messages['email_contato.email'] = __('validation.email', ['attribute' => 'Email do contato']);
        $messages['responsabilidade_frete.in'] = __('validation.in', ['attribute' => 'Responsabilidade do Frete']);
        $messages['transportador_email.email'] = __('validation.email', ['attribute' => 'Email da transportadora']);
        $messages['nota_cliente_numero.max'] = __('validation.max.string', ['attribute' => 'e-mail', 'max' => '250']);
        $messages['nota_cliente_numero.max'] = __('validation.max.string', ['attribute' => 'Nota do cliente', 'max' => '250']);
        $messages['nota_cliente_arquivo.mimes'] =  __('validation.mimes', ['attribute' => 'Nota do cliente', 'values' => 'pdf, xml']);
        $messages['romaneio_arquivo.mimes'] = __('validation.mimes', ['attribute' => 'Romaneio', 'mimes' => 'DOC, DOCX, XLS e XLSX']);
        $messages['romaneio_arquivo.max'] = __('validation.max.file', ['attribute' => 'Romaneio', 'max' => '2000']);
        $messages['nota_remessa.max'] = __('validation.max.string', ['attribute' => 'Romaneio', 'max' => '250']);
        
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