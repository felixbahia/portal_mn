@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table content-produto-movimentos">
    <ul class="nav nav-tabs">
        @foreach($estabelecimentos as $codigo => $estabelecimento)
        <li class="nav-item">
            <a class="nav-link" id="produto_estabelecimento-{{ $codigo }}" data-toggle="tab" href="#produto_estabelecimento_{{ $codigo }}" role="tab" aria-controls="produto_estabelecimento_{{ $codigo }}" aria-selected="false">{{ $estabelecimento }}</a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content pt-3" id="ProdutoHeaderContainer">
        @foreach($estabelecimentos as $codigo => $estabelecimento)
        <div class="tab-pane" id="produto_estabelecimento_{{ $codigo }}" role="tabpanel" aria-labelledby="dados-tab">
            <table class="table table-striped" id="table-filters-dialog">
                <thead>
                    <tr>
                        <th class="sort-date align-middle" rowspan="2">Data</th>
                        <th class="tb_number align-middle" rowspan="2">Documento</th>
                        <th class="align-middle" rowspan="2">CFOP</th>
						<th class="tb_number" rowspan="2">Preço<br>nota</th>
                        <th class="tb_number align-middle" rowspan="2">Aliquota</th>
                        <th class="tb_number align-middle" colspan="2">Quantidade</th>
                        <th class="tb_number align-middle" rowspan="2">Unidade</th>
                        <th class="tb_number border-right " colspan="2">Contábil</th>
                        <th class="tb_number border-right " colspan="2">Médio Gerencial</th>
                        <th class="tb_number" colspan="2">Gerencial</th>
                        <th class="tb_number" colspan="2">Armazem</th>
                        <th class="align-middle" rowspan="2">Tipo</th>
                    </tr>
                    <tr>
                        <th class="tb_number border-right ">nota</th>
                        <th class="tb_number">Saldo</th>

                        <th class="tb_number border-right ">Custo</th>
                        <th class="tb_number border-right ">Saldo</th>

                        <th class="tb_number border-right ">Custo</th>
                        <th class="tb_number border-right ">Saldo</th>

                        <th class="tb_number border-right ">Custo</th>
                        <th class="tb_number">Saldo</th>
                        <th class="tb_number border-right ">Custo</th>
                        <th class="tb_number">Saldo</th>
                   
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimentos[$codigo] as $movimento)
                        <tr>
                            <td data-order='{{ $movimento['data_ordem'] }}' class="sort-date">{{ $movimento['data'] }}</td>
                            <td>{!! $movimento['documento'] !!}</td>
                            <td>{{ $movimento['cfop'] }}</td>

                            <td class="tb_number">{{ $movimento['preco_nota'] }}</td>

                            <td class="tb_number">{{ $movimento['aliquota'] }}</td>

                            <td class="tb_number">{{ $movimento['quantidade_nota'] }}</td>
                            <td class="tb_number">{{ $movimento['quantidade_saldo'] }}</td>

                            <td class="tb_number">{{ $movimento['unidade'] }}</td>

                            <td class="tb_number">{{ $movimento['custo_contabil_valor'] }}</td>
                            <td class="tb_number">{{ $movimento['custo_contabil_saldo'] }}</td>

                            <td class="tb_number">{{ $movimento['custo_mediogerencial_valor'] }}</td>
                            <td class="tb_number">{{ $movimento['custo_mediogerencial_saldo'] }}</td>

                            <td class="tb_number">{{ $movimento['custo_gerencial_valor'] }}</td>
                            <td class="tb_number">{{ $movimento['custo_gerencial_saldo'] }}</td>
                 
                            <td class="tb_number">{{ $movimento['custo_armazem'] }}</td>
                            <td class="tb_number">{{ $movimento['custo_armazem_saldo'] }}</td>
                            <td>{{ $movimento['tipo'] }}</td>

                            

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</div>
<script type="text/javascript">
    table_dialog = [];
    var $height = 200;

    @foreach($estabelecimentos as $codigo => $estabelecimento)
    var table_{{ $codigo  }} = [];
    @endforeach
    $(document).ready(function () {
        $(document).find('.content-produto-movimentos').find('a.exibir-nota').on('click', function(){
            showNotasDetalhesNasajon($(this).data('id'));
        });
        $(document).find('a.exibir-nota-prologos').on('click', function(){
            showNotasDetalhesPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'))
        });
        $(document).find('a.exibir-nota-entrada').on('click', function(){
            showNotaEntrada($(this));
        });
        $(document).find('a.exibir-nota-entrada-prologos').on('click', function(){
            showNotasEntradaPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'));
        });
        
        setTimeout(function(){
            $height = $(document).find(".modal-body:visible").height() - 130;
            $.fn.dataTable.ext.errMode = 'throw';
            $.fn.dataTable.moment('DD/MM/YYYY');
            @foreach($estabelecimentos as $codigo => $estabelecimento)
            table_{{ $codigo }} = $(document).find("#produto_estabelecimento_{{ $codigo }}").find("#table-filters-dialog").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        footer: true,
                        customize: function ( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        },
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            },
                            format: {
                                body: function ( data, row, column, node ) {
                                    return (column === 7 || column === 7 || column === 7 || column === 7 || column === 7 ) ?
                                        data.replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ).toLocaleString('pt-BR') :
                                        data;
                                }
                            }
                        }
                    },
                ],
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
                    { "class": "tb_number", targets: "tb_number" },
                    { "class": "tb_date",  targets: "sort-date" }
                ],
                "order": [0, 'asc']
            });
            @endforeach
            $(document).find('.content-produto-movimentos').find('.nav a:first').tab('show');
            $('[data-toggle="tooltip"]').tooltip();
        }, 250);
		$(document).find('.content-produto-movimentos').find('a[data-toggle="tab"]').off('shown.bs.tab');
		$(document).find('.content-produto-movimentos').find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            @foreach($estabelecimentos as $codigo => $estabelecimento)
            table_{{ $codigo }}.draw();
            @endforeach
            $(document).find('.content-produto-movimentos').find('a.exibir-nota').on('click', function(){
                showNotasDetalhesNasajon($(this).data('id'));
            });
            $(document).find('a.exibir-nota-prologos').on('click', function(){
                showNotasDetalhesPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'))
            });
            $(document).find('a.exibir-nota-entrada').on('click', function(){
                showNotaEntrada($(this));
            });
            $(document).find('a.exibir-nota-entrada-prologos').on('click', function(){
                showNotasEntradaPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'));
            });
        });
    });

    function showNotasDetalhesNasajon($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(document).find('#nota_detalhes').find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find('#nota_detalhes').find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            }
        });

    }

    function showNotaEntrada($this){
		var url = '{{ route('notas_entradas_nasajon.nota')}}';
		var title = 'Detalhes da nota';
		var id = $($this).data('id');
		xhr = $.ajax({
			url: url,
			data: {_token: "{{ csrf_token() }}", id: id},
			method: 'POST',
			success: function(body){
                if(body.status === 'error'){
                    message('Erro',body.message,'');
                }else{
                    createModal("modal_nota_entrada", title, body, 'modal-lg');
                }
			}
		});
	}

    function showNotasDetalhesPrologos(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                link_pedido: true,
                data: data,
                origem: 'PROLOGOS'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }

    function showNotasEntradaPrologos(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('notas_entrada_prologos.modal')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                data: data
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
    
</script>
@endsection 
