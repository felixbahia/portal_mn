@extends('layouts.page-dialog')
@section('content')
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-12">
                <h5>Detalhes - Código: {{ $dados['id'] }} - Produto: {{ $dados['nome_projeto'] }}</h5>
            </div>
        </div>
    </div>
    <div class="content-dialog-table">
        @if(!empty($dados['tecidos']))
            <label>Tecidos</label>
            <table class="table table-striped table-not-edit" id="table-dialog-tecidos">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Código</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['tecidos'] as $tecido) 
                    <tr>
                        <td>{{ $tecido['estabelecimento'] }}</td>
                        <td>{{ $tecido['codigo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['descricao'] }}'>{{ $tecido['descricao'] }}</div></div></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                </tfoot>
            </table>
            <br>
        @endif

        @if(!empty($dados['insumos']))
            <label>Insumos/Acessórios</label>
            <table class="table table-striped table-not-edit table-not-view" id="table-dialog-insumos">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Código</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['insumos'] as $insumo)
                    <tr>
                        <td>{{ $insumo['estabelecimento'] }}</td>
                        <td>{{ $insumo['codigo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['descricao'] }}'>{{ $insumo['descricao'] }}</div></div></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                </tfoot>
            </table>
            <br>
        @endif

        @if(!empty($dados['faccoes']))
            <label>Facções</label>
            <label>Insumos/Acessórios</label>
            <table class="table table-striped table-not-edit table-not-view" id="table-dialog-faccoes">
                <thead>
                    <tr>
                        <th>Tipo de Serviço</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['faccoes'] as $faccao)
                    <tr>
                        <td>{{ $faccao['tipo_de_servico'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                </tfoot>
            </table>
            <br>
        @endif
    </div>
</div>
<script>
	$(document).ready(function(){
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
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
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
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
		});
		$('#table-dialog-faccoes').DataTable({
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
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
        });
	});
</script>
@endsection