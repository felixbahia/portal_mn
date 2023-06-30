@extends('layouts.page-dialog')
@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th class="sort-date align-middle">Data</th>
                <th class="tb_number align-middle">Documento</th>
                <th class="align-middle">CFOP</th>
                <th class="tb_number align-middle">Aliquota</th>
                <th class="tb_number align-middle">Unidade</th>
                <th class="tb_number">Preço</th>
                <th class="tb_number align-middle">Quantidade</th>
                <th class="tb_number align-middle">total</th>
                <th class="align-middle">Tipo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movimentos as $movimento)
                <tr>
                    <td data-sort='YYYYMMDD' class="sort-date">{{ $movimento['data'] }}</td>
                    <td>{!! $movimento['documento'] !!}</td>
                    <td>{{ $movimento['cfop'] }}</td>
                    <td class="tb_number">{{ $movimento['aliquota'] }}</td>
                    <td class="tb_number">{{ $movimento['unidade'] }}</td>
                    <td class="tb_number">{{ $movimento['preco_nota'] }}</td>
                    <td class="tb_number">{{ $movimento['quantidade_nota'] }}</td>
                    <td class="tb_number">{{ $movimento['total'] }}</td>
                    <td>{{ $movimento['tipo'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    var $height = 200;
    $('#table-filters-dialog').find("td").find("div").find("div").off('mouseover');
    $(document).ready(function () {
        $(document).find('a.exibir-nota').on('click', function(){
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
            table_dialog = $(document).find("#table-filters-dialog").DataTable({
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
                                    data = $('<p>' + data + '</p>').text();
                                    if(column === 3 || column === 5 || column === 6 || column === 8 || column === 9 || column === 10 || column === 11 || column === 12 || column === 13 ){
                                        if(data != ''){
                                            numero = data.replace(/[$.]/g,'').replace(',','');
                                            inteiro = Math.floor(numero.length - 2);
                                            decimal = Math.floor(numero.length);
                                            data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                        }else{
                                            data = '';
                                        }
                                    }
                                    return data;
                                },
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
                    { "class": "tb_number", "type": 'num-fmt', "targets": "tb_number" },
                    { "class": "tb_date", "type": "date", "targets": "sort-date" }
                ],
                "order": [0, 'asc']
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
