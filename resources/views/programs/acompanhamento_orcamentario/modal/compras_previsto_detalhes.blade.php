@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-produtos">
        <thead>
            <tr>
                <th>Fornecedor</th>
                <th>Valor Previsto</th>
                <th>Valor Realizado</th>
            </tr>            
        </thead>
        <tbody>
            @foreach ($linhas as $linha)
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $linha["fornecedor"] }}">{{ $linha["fornecedor"] }}</div></div></td>
                <td class="tb_number">{{$linha['previsto']}}</td>
                <td class="tb_number">{{$linha['valor']}}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td>
                <td class="tb_number">{{$total_previsto}}</td>
                <td class="tb_number">{{$total}}</td>
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
                                
                                if(column >= 5 && column != 10 ){
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
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

    function mostrarPedidoComprasDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
            },
            success: function(body){
                createModal("modal_compras_detalhes", "Detalhe do Pedido Compras", body, 'modal-lg');
                var modal = $("#modal_compras_detalhes");
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }
    
        });
    }

    function mostrarNotaEntradaDetalhes($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
    function abrirProjeto($value){

        var $id =$value;
       
      
        var title = 'Detalhes do Importação ';
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