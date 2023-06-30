@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table" style='float: none;'>
        <table class='table table-striped table-filter-agrupamento table-not-edit' id='table-filter-agrupamento'>
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">Estabelecimento</th>
                    <th rowspan="2" class="align-middle">Código do Produto</th>
                    <th rowspan="2" class="align-middle">Descrição</th>
                    <th rowspan="2" class="align-middle tb_number">Estoque</th>
                    <th colspan="2" class="align-middle border-right tb_number">Custo Contabil (Nasajon)</th>
                    <th colspan="2" class="align-middle border-right tb_number">Custo médio Contabil (Portal)</th>
                    <th colspan="2" class="align-middle border-right tb_number">Custo médio Gerencial (Portal)</th>
                    <th colspan="2" class="align-middle tb_number">Custo Gerencial (Portal)</th>
                </tr>
				<tr>
					<th class="align-middle tb_number">valor</th>
					<th class="align-middle border-right tb_number">Total</th>

					<th class="align-middle tb_number">valor</th>
					<th class="align-middle border-right tb_number">Total</th>

					<th class="align-middle tb_number">valor</th>
					<th class="align-middle border-right tb_number">Total</th>

					<th class="align-middle tb_number">valor</th>
					<th class="align-middle tb_number">Total</th>
				</tr>
            </thead>
            <tbody>
                @foreach($produtos as $produto)
					@foreach($produto['estoque'] as $estoque)
					<tr>
						<td>{{ $estoque['estabelecimento'] }}</td>
						<td>{{ $produto['codigo_produto'] }}</td>
						<td>{{ $produto['descricao'] }}</td>                    
						<td>{{ $estoque['estoque'] }}</td>

						<td>{{ $estoque['custo_contabil'] }}</td>
						<td>{{ $estoque['custo_contabil_total'] }}</td>

						<td>{{ $estoque['custo_medio_contabil'] }}</td>
						<td>{{ $estoque['custo_medio_contabil_total'] }}</td>

						<td>{{ $estoque['custo_medio_gerencial'] }}</td>
						<td>{{ $estoque['custo_medio_gerencial_total'] }}</td>

						<td>{{ $estoque['custo_gerencial'] }}</td>
						<td>{{ $estoque['custo_gerencial_total'] }}</td>
					</tr>
					@endforeach 
                @endforeach
            </tbody>
			<tfoot>
				<tr>
					<td colspan="3">Total:</td>
					<td colspan="1">{{ $total['estoque'] }}</td>
					<td colspan="2">{{ $total['custo_contabil'] }}</td>
					<td colspan="2">{{ $total['custo_medio_contabil'] }}</td>
					<td colspan="2">{{ $total['custo_medio_gerencial'] }}</td>
					<td colspan="2">{{ $total['custo_gerencial'] }}</td>
				</tr>
			</tfoot>
        </table>
    </div>

    <script>
        table_dialog_agrupamento = $('#table-filter-agrupamento').DataTable({
            "searching": false,
            "paging": true,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
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
                    "targets": 'tb_number',
                    "class": 'tb_number',
                    "width": '5px' 
                },
                {
                    "targets": "tb_estabelecimento",
                    "width": '5%',
                    "orderable": false,
                },
                {
                    "targets": 1,
                    "width": "15%"
                }
            ],
            "order": [[ 1, "asc" ], [ 0, "asc" ], [2, "asc" ], [3, "asc" ], [4, "asc" ]]

        });
    </script>
@endsection
