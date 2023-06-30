@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-produtos">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Valor</th>
                <th>Taxa Câmbio</th>
                <th>Saldo</th>
                <th>Vencimento</th>
            </tr>            
        </thead>
        <tbody>
            @foreach ($reds as $linha)
            <tr>
                <td>{{ $linha["numero_documento"] }}</td>
                <td class="tb_number">{{$linha['valor']}}</td>
                <td class="tb_number">{{$linha['taxa_cambio']}}</td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-id="{{ $linha["id"] }}" data-numero_documento="{{ $linha["numero_documento"] }}" onclick="abriModalDetalhesRed($(this))">{{ $linha["saldo"] }}</a></td>
                <td class="tb_date">{{$linha['vencimento']}}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td>
                <td class="tb_number">{{$total['valor']}}</td>
                <td></td>
                <td class="tb_number">{{$total['saldo']}}</td>
                <td></td>
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
                                
                                if(column > 0 && column <4){
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

    function abriModalDetalhesRed($this){
        var id = $($this).data("id");
        var numero_documento = $($this).data("numero_documento");
        var title = "Hedge: " + numero_documento;
        $.ajax({
            url: '{{ route('red.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                id : id,
            },
            success: function(body){
                createModal("model_detalhes_reds", title, body, 'modal-lg');
                var modal = $("#model_detalhes_reds");
            }
        });
    }
</script>
@endsection