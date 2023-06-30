@extends('layouts.page-dialog')
@section('content')
<div id="app">
    <div class="content-table">
        @if(!empty($dados['tecidos']))
            <label><b style="font-size: 14px;">Tecidos</b></label>
            <table class="table table-striped table-not-edit table-not-view" id="table-dialog-tecidos">
                <thead>
                    <tr>
                        <th width="20%">Código</th>
                        <th>Descrição</th>
                        <th width="10%" class="tb_number">Consumo</th>
                        <th width="10%" class="tb_number">Custo</th>
                        <th width="10%" class="tb_number">Custo Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['tecidos'] as $tecido)
                    <tr>
                        <td width="20%">{{ $tecido['codigo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['descricao'] }}'>{{ $tecido['descricao'] }}</div></div></td>
                        <td width="10%" class="tb_number">{{ $tecido['consumo'] }}</td>
                        <td width="10%" class="tb_number">{{ $tecido['custo'] }}</td>
                        <td width="10%" class="tb_number">{{ $tecido['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>Total:</td>
                        <td>{{ $dados['total_tecido'] }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        @if(!empty($dados['insumos']))
            <label><b style="font-size: 14px;">Insumos/Acessórios</b></label>
            <table class="table table-striped table-not-edit table-not-view" id="table-dialog-insumos">
                <thead>
                    <tr>
                        <th width="20%">Código</th>
                        <th>Descrição</th>
                        <th width="10%" class="tb_number">Consumo</th>
                        <th width="10%" class="tb_number">Custo</th>
                        <th width="10%" class="tb_number">Custo Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['insumos'] as $insumo)
                    <tr>
                        <td width="20%">{{ $insumo['codigo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['descricao'] }}'>{{ $insumo['descricao'] }}</div></div></td>
                        <td width="10%" class="tb_number">{{ $insumo['consumo'] }}</td>
                        <td width="10%" class="tb_number">{{ $insumo['custo'] }}</td>
                        <td width="10%" class="tb_number">{{ $insumo['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>Total:</td>
                        <td>{{ $dados['total_insumo'] }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        @if(!empty($dados['servicos']))
            <label><b style="font-size: 14px;">Serviços</b></label>
            <table class="table table-striped table-not-edit table-not-view" id="table-dialog-servicos">
                <thead>
                    <tr>
                        <th width="20%">Código</th>
                        <th colspan="3">Serviço</th>
                        <th width="10%" class="tb_number">Custo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['servicos'] as $servico)
                    <tr>
                        <td width="20%">{{ $servico['codigo'] }}</td>
                        @if(empty($servico['tecido']))
                        <td colspan="3"><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $servico['descricao'] }}'>{{ $servico['descricao'] }}</div></div></td>
                        @else
                        <td colspan="3"><div><div data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p><b>Tecido:</b> {{ $servico['tecido'] }}<p><b>Produto Acabado:</b> {{ $servico['produto_acabado'] }}"><a href="#" class="bt-detalhe"></a>{{ $servico['descricao'] }}</div></div></td>
                        @endif
                        <td width="10%" class="tb_number">{{ $servico['custo_total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total:</td>
                        <td class="tb_number">{{ $dados['total_servico'] }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <table class="table table-not-edit table-not-view" id="table-dialog-total">
            <tfoot>
                <tr>
                    <td width="20%"></td>
                    <td></td>
                    <td width="5%" class="tb_number"></td>
                    <td width="15%" class="tb_number"><b>Custo Total:</b></td>
                    <td width="10%" class="tb_number">{{ $dados['custo_total'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    
</div>
<script>
	$(document).ready(function(){

        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover',
            placement: 'right',
            template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
		$('#table-dialog-tecidos').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
		});
		$('#table-dialog-insumos').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
		});
		$('#table-dialog-servicos').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
	});
</script>
@endsection