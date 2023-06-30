@extends('layouts.page-dialog')

@section('content')
<div>
    <b>Custo Médio Gerencial:</b> {{ $dados['custo_medio_gerencial'] }} - 
    <b>Custo Médio Contábil:</b> {{ $dados['custo_medio_contabil'] }} - 
    <b>Unidade:</b> {{ $dados['unidade'] }}
</div>
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th class="sort-date align-middle" rowspan="2">Data</th>
                @if(in_array($dados['estabelecimento'], ['03', '04']))
                    <th class="tb_number align-middle" rowspan="2">Doc.</th>
                @else
                    <th class="tb_number align-middle" rowspan="2">Documento</th>
                @endif
                <th class="align-middle" rowspan="2">CFOP</th>
                <th class="tb_number" rowspan="2">Preço<br>nota</th>
                @if(in_array($dados['estabelecimento'], ['03', '04']))
                <th class="tb_number align-middle" rowspan="2">Aliq.</th>
                    <th class="tb_number align-middle" colspan="2">Quantidade - Armazém</th>
                    <th class="tb_number align-middle" colspan="2">Quantidade - Fiscal</th>
                    <th class="tb_number align-middle"  rowspan="2">Saldo Total</th>
                @else
                    <th class="tb_number align-middle" rowspan="2">Aliquota</th>
                    <th class="tb_number align-middle" colspan="2">Quantidade</th>
                @endif
                <th class="tb_number align-middle" rowspan="2">Unidade</th>
                <th class="tb_number border-right " colspan="2">Contábil</th>
                <th class="tb_number border-right " colspan="2">Médio Gerencial</th>
                <th class="tb_number" colspan="2">Gerencial</th>
                <th class="tb_number" colspan="2">Armazém</th>
             
                <th class="align-middle" rowspan="2">Tipo</th>
                
             
            </tr>
            <tr>

                @if(in_array($dados['estabelecimento'], ['03', '04']))
                    <th class="tb_number border-right ">nota</th>
                    <th class="tb_number">Saldo</th>

                    <th class="tb_number border-right ">nota</th>
                    <th class="tb_number">Saldo</th>
                @else
                    <th class="tb_number border-right ">nota</th>
                    <th class="tb_number">Saldo</th>
                @endif

                <th class="tb_number border-right ">Custo</th>
                <th class="tb_number border-right ">Saldo</th>

                <th class="tb_number border-right ">Custo</th>
                <th class="tb_number border-right ">Saldo</th>

                <th class="tb_number border-right">Custo</th>
                <th class="tb_number">Saldo</th>

               <th class="tb_number border-right">Custo</th>
               <th class="tb_number">Saldo</th>
             
                
            </tr>
        </thead>
        <tbody>
            @foreach ($movimentos as $movimento)
                <tr>
                    <td data-sort='YYYYMMDD' class="sort-date">{{ $movimento['data'] }}</td>
                    <td>{!! $movimento['documento'] !!}</td>
                    <td>{{ $movimento['cfop'] }}</td>

                    <td class="tb_number">{{ $movimento['preco_nota'] }}</td>

                    <td class="tb_number">{{ $movimento['aliquota'] }}</td>

                    @if(in_array($dados['estabelecimento'], ['03', '04']))
                        <td class="tb_number">{{ $movimento['quantidade_nota'] }}</td>
                        <td class="tb_number">{{ $movimento['quantidade_saldo'] }}</td>
                        
                        <td class="tb_number">{{ $movimento['quantidade_nota_fiscal'] }}</td>
                        <td class="tb_number">{{ $movimento['quantidade_saldo_fiscal'] }}</td>

                        <td class="tb_number">{{ $movimento['quantidade_saldo_total'] }}</td>
                    @else
                        <td class="tb_number">{{ $movimento['quantidade_nota'] }}</td>
                        <td class="tb_number">{{ $movimento['quantidade_saldo'] }}</td>
                    @endif

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
                    { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                    { "class": "tb_date", "type": "date", targets: "sort-date" }
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
