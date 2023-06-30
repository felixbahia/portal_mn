@extends('layouts.page-dialog')

@section('content')
@if(decrypt($id_unidade_negocio) !== 'nulo')
<div class="form-row">
    <div class="col-lg-6"> 
        <canvas id="grafico_equipe_individual"></canvas>
    </div>
    <div class="col-lg-5"> 
        <canvas id="grafico_donut_equipe_individual"></canvas>
    </div>  
</div>
@endif
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-dialog-equipes">
            <thead>
                <tr>
                    <th>Membro</th> 
                    <th class="tb_number">Meta do Período</th>
                    <th class="tb_number">Meta Diária</th>
                    <th class="tb_number">Valor Atingido</th>
                    <th class="tb_number">Atingimento %</th>
                    <th class="tb_number">Dif. Meta</th>
                    <th class="td_acao">Cliente</th>
                    <th class="td_acao">Produto</th>
                    <th class="td_acao">Devolução</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendedores as $unidade_negocio)
                    @foreach($unidade_negocio as $codigo_vendedor => $vendedor)
                        <tr>
                            <td>{{ $vendedor['vendedor'] }}</td> 
                            <td class="tb_number">{{ $vendedor['meta'] }}</td>
                            <td class="tb_number">{{ $vendedor['meta_diaria'] }}</td>
                            <td class="tb_number">{{ $vendedor['valor'] }}</td>
                            <td class="tb_number">{{ $vendedor['atingimento_metal_porcetagem'] }}</td>
                            <td class="tb_number">{{ $vendedor['diferenca_meta'] }}</td>
                            <td class="td_acao"><a href="#" class="bt-view" data-id_unidade_negocio="{{ $id_unidade_negocio }}" data-filtro="{{ $filtro }}" data-title="Resumo Cliente - {{ $title }} - {{ $vendedor['vendedor'] }}" data-codigo_vendedor="{{ empty($codigo_vendedor)? 'nulo' : $codigo_vendedor }}" onclick="abrirModalClienteEquipe($(this))"></a></td>
                            <td class="td_acao"><a href="#" class="bt-view" data-id_unidade_negocio="{{ $id_unidade_negocio }}" data-filtro="{{ $filtro }}" data-title="Resumo Cliente - {{ $title }} - {{ $vendedor['vendedor'] }}" data-codigo_vendedor="{{ empty($codigo_vendedor)? 'nulo' : $codigo_vendedor }}" onclick="abrirModalGrupoEquipe($(this))"></a></td>
                            <td class="td_acao"><a href="#" class="bt-view" data-id_unidade_negocio="{{ $id_unidade_negocio }}" data-filtro="{{ $filtro }}" data-title="Resumo Cliente - {{ $title }} - {{ $vendedor['vendedor'] }}" data-codigo_vendedor="{{ empty($codigo_vendedor)? 'nulo' : $codigo_vendedor }}" onclick="abrirModalDevolucaoEquipe($(this))"></a></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-right">Total:</td>
                    <td class="tb_number" id="total_metas">{{ $total_meta }}</td>
                    <td class="tb_number" id="total_metas">{{ $total_meta_diaria }}</td>
                    <td class="tb_number" id="total_valor">{{ $total_valor }}</td>
                    <td class="tb_number" id="total_atingimento_metal_porcetagem">{{ $atingimento_metal_porcetagem }}</td>
                    <td class="tb_number" id="total_diferenca_meta">{{ $diferenca_meta }}</td>
                    <td id="total_cliente"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" data-id_unidade_negocio="{{ $id_unidade_negocio }}" title="Cliente" data-title="Resumo Cliente - {{ $title }}" onclick="abrirModalClienteEquipe($(this))" data-codigo_vendedor="" data-original-title="Cliente"></a></td>
                    <td id="total_produto"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" data-id_unidade_negocio="{{ $id_unidade_negocio }}" title="Produto" data-title="Resumo Cliente - {{ $title }}" onclick="abrirModalGrupoEquipe($(this))"  data-codigo_vendedor="" data-original-title="Produto"></a></td>
                    <td id="total_devolucao"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" data-id_unidade_negocio="{{ $id_unidade_negocio }}" title="Devolução" data-title="Resumo Cliente - {{ $title }}" onclick="abrirModalDevolucaoEquipe($(this))" data-codigo_vendedor="" data-original-title="Visualizar"></a></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var ctx_individual = document.getElementById('grafico_equipe_individual');
        var myBarChart_individual = new Chart(ctx_individual, {
            type: 'bar',
            data: {
                labels:[
                    @foreach($vendedores as $unidade_negocio)
                        @foreach($unidade_negocio as $vendedor)
                            "{{$vendedor['vendedor']}}",
                        @endforeach
                    @endforeach
                ],
                datasets: [
                    {
                        label: "Meta 2020",
                        type: "bar",
                        data: [
                            @foreach($vendedores as $unidade_negocio)
                                @foreach($unidade_negocio as $vendedor)
                                    {{$vendedor['meta_codigo']}},
                                @endforeach
                            @endforeach
                         ""],
                        backgroundColor: "green"
                    },
                    {
                        label: "Atingido",
                        type: "bar",
                        backgroundColor: "blue",
                        data: [
                            @foreach($vendedores as $unidade_negocio)
                                @foreach($unidade_negocio as $vendedor) 
                                    {{$vendedor['valor_codigo']}},
                                @endforeach 
                            @endforeach
                        ""],
                    },
                ]
            },
            options: {
            }
        });

        var metas_porcetagem = 100 - {{ empty($atingimento_metal_porcetagem_codigo)? 0 : $atingimento_metal_porcetagem_codigo }};

        var ctx_donut_equipe = document.getElementById("grafico_donut_equipe_individual").getContext("2d");
        
        if(metas_porcetagem < 0){
            metas_porcetagem = 0;
        }

        var config = {
            type: 'doughnut',
            data: {
                labels: [
                    "Atingido",
                    "Meta",
                ],
                datasets: [{
                    data: [{{$atingimento_metal_porcetagem_codigo}}, metas_porcetagem],
                    backgroundColor: [
                        "blue",
                        "green"
                    ],
                    hoverBackgroundColor: [
                        "#FF6384",
                        "#36A2EB"
                    ]
                }]
            },
            options: {
                elements: {
                    center: {
                        text: "{{$atingimento_metal_porcetagem}}",
                        color: 'blue', // Default is #000000
                        fontStyle: 'Arial', // Default is Arial
                        sidePadding: 20 // Defualt is 20 (as a percentage)
                    }
                },
            }
        };
        var myChart_donut_equipe = new Chart(ctx_donut_equipe, config);

        table_dialog_equipes_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
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
                                if(column >= 1){
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
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Registro Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Registro Encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { targets: 5, width: '50px'},
                { targets: 6, width: '50px'},
                { targets: 7, width: '50px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            ],
            "order": [[ 0, "asc" ]],
        };
        table_equipes = '';
        table_equipes = $(document).find('#table-dialog-equipes').DataTable(table_dialog_equipes_options);
        table_equipes.draw();

        setTimeout(function(){
            table_equipes.draw(false);
        }, 200);
    });

    function randomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    function abrirModalClienteEquipe($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        var codigo_vendedor = $($this).data("codigo_vendedor");
        $.ajax({
            url: '{{ route('mapa_venda.modal.cliente') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                title: title,
                codigo_vendedor: codigo_vendedor,
            },
            success: function(body){
                createModal('modal_cliente', title, body, "modal-lg");
                var modal = $("#modal_cliente");
            }
        });
    }

    function abrirModalGrupoEquipe($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var codigo_cliente = "";
        var title = $($this).data("title");
        var codigo_vendedor = $($this).data("codigo_vendedor");
        $.ajax({
            url: '{{ route('mapa_venda.modal.grupo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_cliente: codigo_cliente,
                title: title,
                codigo_vendedor: codigo_vendedor
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function abrirModalDevolucaoEquipe($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        var codigo_vendedor = $($this).data("codigo_vendedor");
        $.ajax({
            url: '{{ route('mapa_venda.modal.devolucao') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_vendedor: codigo_vendedor
            },
            success: function(body){
                createModal('modal_devolucao', title, body, "modal-lg");
                var modal = $("#modal_devolucao");
            }
        });
    }

</script>