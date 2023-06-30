@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th rowspan="2">Origem</th>
                <th rowspan="2" class="tb_number">Documento</th>
                <th rowspan="2" class="sort-date">Data</th>
                <th rowspan="2" class="sort-date">Data Ult. Atua.</th>
                <th rowspan="2">Tipo</th>
                <th rowspan="2">Fornecedor/Cliente</th>
                <th colspan="2" class="tb_number border-right">Fiscal</th>
                <th colspan="2" class="tb_number border-right">Em Terceiros</th>
                <th colspan="2" class="tb_number">Armazém</th>
            </tr>
            <tr>
                <th class="tb_number border-right">quantidade</th>
                <th class="tb_number border-right">Saldo</th>
                <th class="tb_number border-right">quantidade</th>
                <th class="tb_number border-right">Saldo</th>
                <th class="tb_number border-right">quantidade</th>
                <th class="tb_number">Saldo</th>

            </tr>
        </thead>
        <tbody>
            @if(!empty($movimento_estoque['movimento_estoque']))
            @foreach ($movimento_estoque['movimento_estoque'] as $movimento)
            <tr>
                <td>{{ $movimento['origem'] }}</td>
                <td>{!! $movimento['documento'] !!}</td>
                <td class="sort-date">{{ $movimento['data'] }}</td>
                <td class="sort-date">{{ $movimento['data_atualizacao'] }}</td>
                <td>{{ $movimento['tipo'] }}</td>
                <td>{{ $movimento['cliente_fornecedor'] }}</td>

                <td class="tb_number">{{ $movimento['fiscal_quantidade'] }}</td>
                <td class="tb_number">{{ $movimento['fiscal_saldo'] }}</td>

                <td class="tb_number">{{ $movimento['em_terceiros_quantidade'] }}</td>
                <td class="tb_number">{{ $movimento['em_terceiros_saldo'] }}</td>

                <td class="tb_number">{{ $movimento['armazem_quantidade'] }}</td>
                <td class="tb_number">{{ $movimento['armazem_saldo'] }}</td>
            </tr>
            @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number">Total:</td>
                <td class="tb_number"></td>
                <td class="tb_number">{!! $movimento_estoque['saldo_fiscal'] !!}</td>
                <td class="tb_number"></td>
                <td class="tb_number">{!! $movimento_estoque['saldo_em_terceiros'] !!}</td>
                <td class="tb_number"></td>
                <td class="tb_number">{!! $movimento_estoque['saldo_armazem'] !!}</td>
            </tr>
        </tfoot>
    </table>
    <div class="total_movimentacao">
        <div><span><b>Saldo Atual:</b> {!! $movimento_estoque['saldo'] !!}</span></div>
        <div><span><b>Quantidade saida:</b> {!! $movimento_estoque['saldo_saida'] !!}</span></div>
        <div><span><b>Quantidade entrada:</b> {!! $movimento_estoque['saldo_entrada'] !!}</span></div>
    </div>
</div>
<script type="text/javascript">
    table_dialog = [];
    var $height = 200;
    $('#table-filters-dialog').find("td").find("div").find("div").off('mouseover');
    $(document).ready(function () {
        $(document).find('a.exibir-nota').on('click', function(){
            showNotasDetalhesNasajon($(this).data('id'));
        });
        $(document).find('a.exibir-nota-entrada').on('click', function(){
            showNotaEntrada($(this));
        });
        setTimeout(function(){
            $height = $(".modal-body").height() - 130;
            $.fn.dataTable.moment('DD/MM/YYYY HH:mm');
            table_dialog = $("#table-filters-dialog").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": 550,
                "scrollCollapse": true,
                "paging": false,
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
                "order": [[ 2, "asc" ]]
            });
            $('[data-toggle="tooltip"]').tooltip();
        }, 250);
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
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

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
</script>
@endsection 