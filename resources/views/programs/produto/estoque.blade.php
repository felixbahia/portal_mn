@extends('layouts.page-dialog')

@section('content')
    <div class="row_title_pecapeca">
        <div>
            <label>Estabelecimento:</label>
            <span>{{ $dados["estabelecimento"] }}</span>
        </div>
        <div>
            <label>Código Produto:</label>
            <span>{{ $dados["codigo"] }}</span>
        </div>
        <div>
            <label>Total Estoque Liquido:</label>
            <span>{{ $dados["total_estoque"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Total reserva Estoque Liquido:</label>
            <span>{{ $dados["total_reserva"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Unidade:</label>
            <span>{{ $dados["unidade"] }}</span>
        </div>
        <div>
            <label>Total Peça a Peça:</label>
            <span>{{ $dados["total_pecapeca"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Total reserva Peça a Peça:</label>
            <span>{{ $dados["total_reserva_pecapeca"] }}</span>
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table_peca_peca">
                <thead>
                    <tr>
                        <th class="tb_number">Peça Pai</th>
                        <th class="tb_number">Peça</th>
                        <th class="tb_number">PCMN</th>
                        <th class="sort-date">Data de entrada</th>
                        <th class="tb_number">Documento de entrada</th>
                        <th class="tb_number">Quantidade</th>
                        <th>Localização</th>
                        <th>Reserva</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados["peca_peca"] as $value)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["lote_pai"] }}"><a href="#" onclick="showRastreabilidade('{{ $value["volume"] }}')">{{ $value["lote_pai"] }}</a></div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["volume"] }}"><a href="#" onclick="showRastreabilidade('{{ $value["volume"] }}')">{{ $value["volume"] }}</a></div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["pcmn"] }}">{{ $value["pcmn"] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["data_entrada"] }}">{{ $value["data_entrada"] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["documento_entrada"] }}">
                                @if(!empty($value["nota_id"]))
                                <a href="#" onclick="showNotaEntradaDetalhes('{{ $value["nota_id"] }}')">{{ $value["documento_entrada"] }}</a>
                                @else
                                {{ $value["documento_entrada"] }}
                                @endif
                            </div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["quantidade"] }}">{{ $value["quantidade"] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["localizacao"] }}">{{ $value["localizacao"] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["empenho"] }}">{{ $value["empenho"] }}</div></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script>
        $('[data-toggle="tooltip"]').tooltip();
        
        setTimeout(function(){
            $height = $(".modal-body").height() - 130;

            $.fn.dataTable.moment('DD/MM/YYYY');
            
            var table_lancamentos = $("#table_peca_peca").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "pageLength": 15,
                "language": {
                    "decimal":        ".",
                    "emptyTable":     "Nenhuma peça vinculada encontrada",
                    "infoPostFix":    "",
                    "thousands":      ",",
                    "loadingRecords": "Carregando...",
                    "processing":     "Processando...",
                    "zeroRecords":    "Nenhuma peça vinculada encontrada",
                    "paginate": {
                        "first":      "<<",
                        "last":       ">>",
                        "next":       ">",
                        "previous":   "<"
                    }
                },
                "pagingType": "full_numbers",
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: ' ',
                        title: '',
                        footer: true,
                        customize: function ( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            $('c[r=G7] t', sheet).attr( 's', '0' );
                        },
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            },
                            format: {
                                body: function ( data, row, column, node ) {

                                    if([0,1].includes(column)){
                                        return "\0" + $(data).text();
                                    }
                                    else if(column === 5){
                                        return $(data).text().replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ).trim();
                                    }
                                    else{
                                        return $(data).text().trim(); 
                                    }
                                }
                            }
                        }
                    },
                ],
                "columnDefs": [
                    { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                    { "class": "tb_date", targets: "sort-date" }
                ],
                "order": [[ 6, 'asc' ],[ 2, 'asc' ]]
            });

            $('[data-toggle="tooltip"]').tooltip();
        
        }, 100);

        function showNotaEntradaDetalhes($id){
            var url = '{{ route('notas_entradas_nasajon.nota')}}';
            var title = 'Detalhes da nota';
            var id = $id;
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

        function showRastreabilidade($codigo_fracao){
            var url = '{{ route('rastreabilidade.modal.fracoes')}}';
            var title = 'Detalhes da nota';
            var codigo_fracao = $codigo_fracao;

            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", codigo_fracao: codigo_fracao},
                method: 'POST',
                success: function(body){
                    if(body.status === 'error'){
                        message('Erro',body.message,'');
                    }else{
                        createModal("modal_rastreabilidade", title, body, 'modal-lg');
                    }
                }
            });
        }
    </script>
@endsection