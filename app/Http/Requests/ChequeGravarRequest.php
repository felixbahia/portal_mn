<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Crypt;

use App\Cheque;
use App\ClienteNasajon;

class ChequeGravarRequest extends FormRequest
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
        return [
            'tipo' => [
                Rule::in(['cheque', 'deposito', 'dinheiro'])
            ],
            'cliente' => [
                'required',
                function($attribute, $value, $fail) {
                    $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \''. $value . '\'')->first();

                    if(is_null($clienteNasajon)){
                        return $fail('Cliente inválido.');
                    }
                }
            ],
            'banco' => [
                'nullable',
                'required_if:tipo,cheque',
                'required_if:tipo,deposito'
            ],
            'agencia' => [
                'nullable',
                'required_if:tipo,cheque',
                'required_if:tipo,deposito'
            ],
            'conta' => [
                'nullable',
                'required_if:tipo,cheque',
                'required_if:tipo,deposito'
            ],
            'numero_cheque' => [
                'nullable',
                'required_if:tipo,cheque',
                'required_if:tipo,deposito',
                function($attribute, $value, $fail) {

                    if($this->tipo != 'dinheiro'){
                        $chequeQuery = Cheque::where('banco', $this->banco)
                            ->where('tipo', $this->tipo)
                            ->where('agencia', $this->agencia)
                            ->where('conta', $this->conta)
                            ->where('numero_cheque', $this->numero_cheque)
                            ->whereNull('deleted_at');
    
                        if(isset($this->id) && !empty($this->id)){
                            
                            try {
                                $id = Crypt::decrypt($this->id);
                            } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
                                return $fail('Lançamento inválido! Atualize a tela.');
                            }
                            
                            $chequeQuery->where('id', '!=', $id);
    
                        }
    
                        if($chequeQuery->exists()){
                            return $fail('Lançamento já cadastrado!');
                        }
                    }
                }
            ],
            'valor' => [
                'required',
                function($attribute, $value, $fail) {
                    
                    if(isset($this->id) && !empty($this->id)){
                        try {
                            $id = Crypt::decrypt($this->id);
                        } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
                            return $fail('Lançamento inválido! Atualize a tela.');
                        }
                        
                        $chequeObj = Cheque::with('pedidos_prepagos')->find($id);
                        $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \''. $this->cliente . '\'')->first();

                        if(!empty($clienteNasajon)){
                            $cpf_cnpj = $clienteNasajon->cpf_cnpj;
                        }
                        else{
                            $cpf_cnpj = '';
                        }

                        if($chequeObj->pedidos_prepagos->isNotEmpty() && 
                            (
                                $this->tipo != $chequeObj->tipo ||
                                $cpf_cnpj != $chequeObj->cliente_cpf_cnpj ||
                                $this->banco != $chequeObj->banco ||
                                $this->agencia != $chequeObj->agencia ||
                                $this->conta != $chequeObj->conta ||
                                $this->numero_cheque != $chequeObj->numero_cheque ||
                                parserNumber($this->valor) != $chequeObj->valor
                            )
                        ){
                            return $fail('Lançamento já vinculado a pedido pré-pago não pode ser alterado!');
                        }

                    }
                }
            ],
            'bom_para' => [
                'nullable',
                'required_if:tipo,cheque'
            ],
            'status' => [
                'nullable',
                'required_if:tipo,cheque',
                'required_if:tipo,deposito',
                Rule::in(['aberto', 'baixado', 'devolvido'])
            ]
        ];
    }

    public function messages(){
        return[
            'tipo.required' => 'Escolha um tipo!',
            'tipo.in' => 'Tipo inválido!',
            'cliente.required' => 'Selecione um cliente!',
            'banco.required_if' => 'Digite um banco!',
            'agencia.required_if' => 'Digite uma agência!',
            'conta.required_if' => 'Digite uma conta!',
            'numero_cheque.required_if' => 'Digite o número do lançamento!',
            'valor.required' => 'Digite um valor!',
            'bom_para.required' => 'Digite uma data!',
            'status.in' => 'Status inválido!',
            'pedidos_prepagos.required_if' => 'Selecione quais pedidos este cheque baixa!'
        ];
    }
}
