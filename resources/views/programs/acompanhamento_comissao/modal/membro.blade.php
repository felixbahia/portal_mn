@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="acompanhamento_comissao_vendedores-tab" data-toggle="tab" href="#acompanhamento_comissao_vendedores" role="tab" aria-controls="acompanhamento_comissao_vendedores" aria-selected="false">Vendedores</a>
    </li>
    @if(in_array(Auth::id(), $id_gerente) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial')|| Auth::user()->hasRole('RH'))
    <li class="nav-item">
		<a class="nav-link" id="acompanhamento_comissao_gerente-tab" data-toggle="tab" href="#acompanhamento_comissao_gerente" role="tab" aria-controls="acompanhamento_comissao_gerente" aria-selected="false">Gerente</a>
    </li>
    @endif
</ul>
<div class="tab-content pt-3" id="AcompanhamentoComissaoHeaderContainer">
    <div class="tab-pane show active" id="acompanhamento_comissao_vendedores" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-dialog-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-vendedores">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th class="tb_number_150">Meta</th>
                        <th class="tb_number_150">Faturamento</th>
                        <th class="tb_number_150">Atingimento</th>
                        <th class="tb_number_150">Comissão</th>
                        <th class="tb_number_150">Valor Individual</th>
                        <th class="tb_number_150">Valor Equipe</th>
                        <th class="tb_number_150">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendedores as $unidade_negocio)
                        @foreach($unidade_negocio as $vendedor)
                            <tr>
                                <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $vendedor['vendedor'] }}'>{{ $vendedor['vendedor'] }}</div></div></td>
                                <td>{{ $vendedor['meta'] }}</td>
                                <td>{{ $vendedor['valor'] }}</td>
                                <td>{{ $vendedor['atingimento_metal_porcetagem'] }}</td>
                                <td>{{ $vendedor['comissao_porcetagem'] }}</td>
                                @if($vendedor['tipo'] === 'Vendedor Interno')
                                <td>{{ $vendedor['valor_individual'] }}</td>
                                <td>{{ $vendedor['valor_equipe'] }}</td>
                                <td>{{ $vendedor['valor_total'] }}</td>
                                @else
                                <td></td>
                                <td></td>
                                <td></td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="tb_number_150">Total:</td>             
                        <td class="tb_number_150">{{ $total_meta }}</td>
                        <td class="tb_number_150">{{ $total_faturado }}</td>
                        <td class="tb_number_150">{{ $total_atingimento }}</td>
                        <td></td>
                        <td class="tb_number_150">{{ $total_valor_individual }}</td>
                        <td class="tb_number_150">{{ $total_valor_equipe }}</td>
                        <td class="tb_number_150">{{ $total_valor_total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @if(in_array(Auth::id(), $id_gerente) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretor') || Auth::user()->hasRole('Diretor Comercial') || Auth::user()->hasRole('RH'))
        <div class="tab-pane" id="acompanhamento_comissao_gerente" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <table class="table table-striped table-not-edit table-not-view" id="table-gerente">
                    <thead>
                        <tr>
                            <th>Gerente</th>
                            <th class="tb_number_150">Comissão</th>
                            <th class="tb_number_150">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gerentes as $gerente)
                            <tr>
                                <td>{{ $gerente['vendedor'] }}</td>
                                <td class="tb_number_150">{{ $gerente['comissao_porcetagem'] }}</td>
                                <td class="tb_number_150">{{ $gerente['valor_total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
<script>
    $(document).ready( function () {
        $(document).find('#table-vendedores').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    'targets': 0,
                    'min-width': '200px'
                },
                { "class": "tb_number_150", type: 'num-fmt', targets: "tb_number_150"},
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
    });
</script>
@endsection