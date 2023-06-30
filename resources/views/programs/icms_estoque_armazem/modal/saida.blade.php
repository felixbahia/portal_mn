@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-dialog-fechamento_caixa_anos">
        <thead>
            <tr>
                <th>Nota</th>
                <th >Fornecedor</th>
                <th class="tb_date">Data Saída</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">ICMS</th>
                <th class="text-right">CFOP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $dado)
                <tr>
                    <td>{!!$dado['documento']!!}</td>
                    <td>{{$dado['fornecedor']}}</td>
                    <td><span style="display:none">{{ $dado['data_codigo'] }}</span>{{$dado['data']}}</td>
                    <td>{{$dado['valor']}}</td>
                    <td>{{$dado['icms']}}</td>
                    <td class="text-right">{{$dado['cfop']}}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td class="text-right">Total:</td>
                <td class="tb_number">{{$total['valor']}}</td>
                <td class="tb_number">{{$total['icms']}}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $(document).find('a.exibir-nota').on('click', function(){
            showNotasDetalhesNasajon($(this).data('id'));
        });
        $(document).find('a.exibir-nota-entrada').on('click', function(){
            showNotaEntrada($(this));
        });

        $height = $(document).find(".modal").height() - 200;
        table_fechamento_ano = $('#table-dialog-fechamento_caixa_anos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    customize: function( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column > 2){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            },
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
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
                    "class": "tb_number", 
                    "type": 'num-fmt', 
                    "targets": "tb_number",
                    render: $.fn.dataTable.render.number( '.', ',', 2 )
                },
                { "class": "tb_date", targets: "tb_date" }
            ]
        });

        setTimeout(function(){
            table_fechamento_ano.draw(false);
        }, 300);
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
