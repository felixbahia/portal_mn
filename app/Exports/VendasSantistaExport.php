<?php

namespace App\Exports;

use App\VendasSantista;
use App\ClienteNasajon;

use Maatwebsite\Excel\Concerns\FromCollection;

use Illuminate\Support\Facades\DB;

class VendasSantistaExport implements FromCollection
{
    public function collection()
    {
        $vendasSantistaObj = VendasSantista::with('cliente')
            ->whereBetween('created_at', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
            ->get();

        $retorno = collect([[
            'CNPJ',
            'Razão Social',
            'CEP',
            'Endereço',
            'Número',
            'Bairro',
            'Cidade',
            'Estado',
            'Nº Nota Fiscal',
            'Valor',
            'Data Emissão'
        ]]);

        $vendasSantistaObj->each(function ($venda) use(&$retorno){

            if(!empty($venda->cliente)){
                $cep = $venda->cliente->cep;
                $endereco = $venda->cliente->tipologradouro . ' ' . $venda->cliente->logradouro;
                $numero = $venda->cliente->numero;
                $bairro = $venda->cliente->bairro;
                $cidade = $venda->cliente->cidade;
                $uf = $venda->cliente->uf;
            }
            else{
                $cep = '';
                $endereco = '';
                $numero = '';
                $bairro = '';
                $cidade = '';
                $uf = '';
            }

            $novaLinha = [                
                $venda->cliente_cnpj,
                $venda->cliente_nome,
                $cep,
                $endereco,
                $numero,
                $bairro,
                $cidade,
                $uf,
                $venda->nota_fiscal,
                $venda->valor,
                parserData($venda->emissao)
            ];

            $retorno->push($novaLinha);

        });

        return $retorno;

    }
}
