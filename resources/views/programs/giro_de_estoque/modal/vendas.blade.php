@extends('layouts.page-dialog')

@section('content')
    <div class="content-view">
        <div class="content-dialog-table" style='float: none;'>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Marca</th>
                        <th>Linha</th>
                        <th>Código</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $produto['grupo'] }}</td>
                        <td>{{ $produto['marca'] }}</td>
                        <td>{{ $produto['linha'] }}</td>
                        <td>{{ $produto['codigo'] }}</td>
                        <td>
                            <div><div data-toggle="tooltip" data-html="true" title="{{ $produto["descricao"] }}">{{ $produto["descricao"] }}</div></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="content-dialog-table" style='float: none;'>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Dias</th>
                            <th>Quantidade</th>
                            <th>Média Por Dia</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if(!empty($quantidade['quantidade_30_dias']))
                        <tr>
                            <td class="text-center"><strong>30</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_30_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_30_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_60_dias']))
                        <tr>
                            <td class="text-center"><strong>60</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_60_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_60_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_90_dias']))
                        <tr>
                            <td class="text-center"><strong>90</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_90_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_90_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_120_dias']))
                        <tr>
                            <td class="text-center"><strong>120</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_120_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_120_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_150_dias']))
                        <tr>
                            <td class="text-center"><strong>150</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_150_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_150_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_180_dias']))
                        <tr>
                            <td class="text-center"><strong>180</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_180_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_180_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_210_dias']))
                        <tr>
                            <td class="text-center"><strong>210</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_210_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_210_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_240_dias']))
                        <tr>
                            <td class="text-center"><strong>240</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_240_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_240_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_270_dias']))
                        <tr>
                            <td class="text-center"><strong>270</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_270_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_270_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_300_dias']))
                        <tr>
                            <td class="text-center"><strong>300</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_300_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_300_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_330_dias']))
                        <tr>
                            <td class="text-center"><strong>330</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_330_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_330_dias'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($quantidade['quantidade_360_dias']))
                        <tr>
                            <td class="text-center"><strong>360</strong></td>
                            <td class="tb_number">{{ $quantidade['quantidade_360_dias'] }}</td>
                            <td class="tb_number">{{ $media['media_360_dias'] }}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class='text-right'>Total:</td>
                            <td class='tb_number'>{{ $totalQuantidade }}</td>
                        </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <h4>Filtros</h4>
<div class="content-fields">

<div class="content-dialog-table">
    <div class="content-table">
        <table class="order-column table-striped" id="table-mes-a-mes-p">
            <thead>
                <tr>
                    <th class="width-table-align-120">Data</th>
                    @foreach ($th as $dado)
                        <th class="tb_date width-table-align-120">{{ $dado }}</th>
                    @endforeach
                    <th class="width-table-align-120">Total</th>
                    <th class="width-table-align-120"></th>
                    
                </tr>
            </thead>
            <tbody>

                <tr>    
                    <td class="width-table-align-120">Vendas</td>
                    @foreach ($th as $key => $data)
                    <td class="tb_number width-table-align-120">
                        {{ $dados[$key]['venda'] }}
                    </td>
                    @endforeach
                    <td class="tb_number width-table-align-120">
                        {{ $total['vendido'] }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection


