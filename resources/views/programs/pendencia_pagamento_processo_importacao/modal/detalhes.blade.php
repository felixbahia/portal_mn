@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-produtos">
        <thead>
            <tr>
                <th>Fornecedor</th>
                <th>Proforma</th>
                <th>PCMN</th>
                <th>Valor</th>
            </tr>            
        </thead>
        <tbody>
            @foreach ($proformas as $linha)
            <tr>
                <td>{{ $linha['fornecedor'] }}</td>
                <td><a href="#" data-toggle='tooltip' data-html='true' data-id="{{ $linha['id'] }}" data-title="{{ $linha['titulo'] }}" title='Visualizar' onclick="abrirProjeto($(this))">{{ $linha["proforma"] }}</a></td>
                <td>{{ $linha['pcmn_codigo'] }}</td>
                <td class="tb_number">{{ $linha["valor"] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td class="tb_number">Total :</td>
                <td class="tb_number">{{$total['valor']}}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-filters-produtos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                
                                if(column > 0){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            },    
                            footer: function(data, column) {

                             
                                if(column == 5 || column == 7 || column == 8 || column == 9 || column ==11 || column == 12){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }
                              data = $('<p>' + data + '</p>').text();
                              return data;
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
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "tb_date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

    function abrirProjeto($value){
        var $id = $value.data("id");
        var $title = $value.data("title");

        var title = 'Detalhes do Importação ' + $title;
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('importacao.modal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id 
            },
            success: function (data){
                createModal('detalhes_projeto', title, data, 'modal-lg');
            }
        });
    }
</script>
@endsection