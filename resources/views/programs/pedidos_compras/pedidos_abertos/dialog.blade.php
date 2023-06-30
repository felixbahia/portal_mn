@extends('layouts.page-dialog')

@section('content')

{!! Form::hidden('unidade', $dados['unidade'], ['id' => 'unidade']) !!}
{!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
{!! Form::hidden('data', $dados['data_recebimento'], ['id' => 'data']) !!}
<div class="col-lg-12">
	<div class="row">
		<div class="col-lg-2">
			<h5>Pedido nº {{ $dados['pcmn'] }}</h5>
        </div>
        <div class="col-lg-2 col-sm-6 col-xl-4">
            <h5>Status do Pedido: {{ $dados['situacao'] }}</h5>
        </div>
    </div>
    <div class='pedido_detalhes_content'>
        @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
        <div class="row">
            <div class="col-sm-12">
                <b>Fornecedor: </b><br>
                {{ $dados['fornecedor'] }}
            </div>
        </div>
            @if (!empty($dados['proforma']))
            <div class="row">
                <div class="col-sm-12">
                    <b>Proforma: </b><br>
                    {{ $dados['proforma'] }}
                </div>
            </div>
            @endif
        @endif
        <div class="row">
			<div class="col-sm-2">
				<b>Total do Pedido:</b><br>
				{{ $dados['total_pedido'] }}
			</div>
        </div>
        <div class="row">
			<div class="col-sm-2">
				<b>Estabelecimento:</b><br>
				{{ $dados['unidade'] }}
			</div>
			<div class="col-sm-2">
				<b>Data Previsão de Recebimento:</b><br>
				{{ $dados['data_previsao_recebimento'] }} 
			</div>
			<div class="col-sm-2">
				<b>Data Recebimento:</b><br>
				{{ $dados['data_recebimento'] }} 
			</div>
		</div>
    </div>
</div>
<br>
<br>
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-dialog">
        <thead>
            <tr>
                <th rowspan="2" class="align-middle">Código</th>
                <th rowspan="2" class="align-middle">Grupo</th>
                <th rowspan="2" class="align-middle">Descrição</th>
                <th rowspan="2" class="align-middle">Status</th>
                <th rowspan="2" class="tb_number porcetagem align-middle">FOB Compra @if($dados['codigo_estabelecimento'] == 3) Dolar @endif</th>
                <th rowspan="2" class="tb_number porcetagem align-middle">Preço Venda @if($dados['codigo_estabelecimento'] == 3) Dolar @endif</th>
                <th colspan="4" class="tb_number porcetagem align-middle">Quantidade</th>
                <th rowspan="2" class="tb_number porcetagem align-middle">%Compra/Venda</th>
            </tr>
            <tr>
                <th class="tb_number porcetagem align-middle">Comprada</th>
                <th class="tb_number porcetagem align-middle">Recebida</th>
                <th class="tb_number porcetagem align-middle">Saldo</th>
                <th class="tb_number porcetagem align-middle">Pedido de Venda</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            <tr>
                <td>{{ $item['cod_produto'] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['grupo'] }}">{{ $item['grupo'] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['descricao_produto'] }}">{{ $item['descricao_produto'] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['situacao'] }}">{{ $item['situacao'] }}</div></div></td>
                <td>{{ $item['preco_compra'] }}</td>
                <td>{{ $item['preco_dolar'] }}</td>
                <td>{{ $item['quantidade_comprada'] }}</td>
                <td>{{ $item['quantidade_recebida'] }}</td>
                <td>{{ $item['quantidade_saldo'] }}</td>
                <td>{{ $item['quantidade_vendida'] }}</td>
                <td>{{ $item['porcetagem'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Total:</td>
                <td class="tb_number">{{ $dados['qtde_comprada'] }}</td>
                <td class="tb_number">{{ $dados['quantidade_recebida']}}</td>
                <td class="tb_number">{{ $dados['quantidade_saldo']}}</td>
                <td class="tb_number">{{ $dados['qtde_vendida']}}</td>
                <td class="tb_number porcetagem">{{ $dados['porcetagem'] }}</td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-dialog').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    text: '<i class="btn-excel"></i>',
                    title: 'Exportar para excel',
                    action: function(){
                        $('<form action="{{ route('pedidos_compras.pedidos_abertos.exportar_excel_dialog') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="export_excel" value="{{ $export_excel }}">\
                        <input type="hidden" name="order" value="0" />\
                        <input type="hidden" name="columns" value="0" />\
                        <input type="hidden" name="start" value="0" />\
                        <input type="hidden" name="length" value="0" />\
                        <input type="hidden" name="draw" value="0" />\
                        </form>').appendTo('body').submit().remove();
                    }
                },
            ],
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
                { "class": "porcetagem", targets: "porcetagem" },
                { "class": "tb_date", targets: "sort-date" },
                { "width": "35%", targets: [2] },
                { "width": "15%", targets: [0,1,7] },
                { "width": "5%", targets: [3,4,5,6,8] }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
    });
</script>
@endsection
