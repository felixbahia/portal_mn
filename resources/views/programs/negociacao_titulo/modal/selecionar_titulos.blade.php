@extends('layouts.page-dialog')

@section('content')
<form action="#" name="form_titulo_para_renegociacao_filter_titulos_para_renegociacao" id="form_titulo_para_renegociacao_filter_titulos_para_renegociacao" onsubmit="return false;">
    @csrf
    <div class="content-filter-dialog">	
        {!! Form::hidden('cliente_codigo', $cliente_codigo, ["id" => 'cliente_codigo']) !!}
        {!! Form::hidden('cliente_nome', $cliente_nome, ["id" => 'cliente_nome']) !!}
        <div class="content-fields">
            <div class="col-lg-3">
                {!! Form::text('titulo', '', ['id' => 'titulo', 'placeholder' => 'Título', 'class' => 'form-control']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('data_inicial', '', ['id' => 'data_inicial', 'placeholder' => 'Data Inicial', 'class' => 'form-control data']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('data_final', '', ['id' => 'data_final', 'placeholder' => 'Data Final', 'class' => 'form-control data']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::select('tipo', $tipos, 'todos', ['id' => 'tipo', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform_titulo_para_renegociacao-baixa-titulos" id="btn-filterform_titulo_para_renegociacao-baixa-titulos" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform_titulo_para_renegociacao" id="btn-clearform_titulo_para_renegociacao" class="btn-clear" value="Limpar busca" />
        </div>
    </div>
    <div class="content-dialog-table">
        <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
            <thead>
                <tr>
                    <th>Selec.<input id="selecione_todos" name="selecione_todos" type="checkbox" autocomplete="off"></th>
                    <th>Estab.</th>
                    @if (count($cod_cliente)>1)
                    <th>Cliente</th>
                    @endif
                    <th>Título</th>
                    <th class="tb_number">Pa.</th>
                    <th class="sort-date">Data de Emissão</th>
                    <th class="sort-date">Data de Vencimento</th>
                    <th class="tb_number">Valor Original</th>
                    <th class="tb_number">Juros</th>
                    <th class="tb_number">Valor (Saldo)</th>
                    <th class="tb_number">Juros Diários</th>
                    <th class="sort-date">Início Juros</th>
                    <th class="tb_number">Desconto</th>
                    <th class='icone'>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados as $value)
                <tr>
                    <td>{!! Form::checkbox('titulos_selecionado[]', $value['titulo_id'], '', ['id' => 'titulos_selecionado']) !!}</td>
                    <td>
                        <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento_nome"] }}">{{ $value["estabelecimento"] }}</div></div>
                    </td>
                    @if (count($cod_cliente)>1)
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["nome_cliente"] }}">{{ $value["nome_cliente"] }}</div></div></td>
                    @endif
                    <td>{!! $value["numero"] !!}</td>
                    <td>{{ $value["parcela"] }}</td>
                    <td>{{ parserData($value["data_emissao"]) }}</td>
                    <td>{!! $value["data_vencimento"] !!}</td>
                    <td>{{ parserValor($value["valor_original"]) }}</td>
                    <td>{{ $value["juros_cobrados"] >0?parserValor($value["juros_cobrados"]):'' }}</td>
                    <td>{{ parserValor($value["valor"]) }}</td>
                    <td>{{ $value["percentual_juros_diarios"] >0?parserQtd3CasaDecimais($value["percentual_juros_diarios"]):'' }}</td>
                    <td>{{ !empty($value["data_juros"])?parserData($value["data_juros"]):'' }}</td>
                    <td>{{ $value["desconto"] >0?parserValor($value["desconto"]):'' }}</td>
                    <td>{!! $value["nota_numero"] !!}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
            </tfoot>
        </table>
    </div>
    <div class="col-sm-12 mt-1" id="button-bottom">
        <button type="button" id="adicionar_renegociacao" class="btn btn-success float-right">Adicionar</button>
    </div>
</form>

<script type="text/javascript">

    $(document).ready(function(){
        form_titulo_para_renegociacao = $(document).find("#form_titulo_para_renegociacao_filter_titulos_para_renegociacao");

        $(document).find('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        table_filters_dialog_titulos_renegociacao = $('#table-filters-dialog_titulos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "48vh",
            "autoWidth": false,
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
                { "class": "tb_date", targets: "sort-date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 1, 'asc' ]]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_titulos_renegociacao.draw();

        form_titulo_para_renegociacao.find("#btn-filterform_titulo_para_renegociacao-baixa-titulos").off("click");
        form_titulo_para_renegociacao.find("#btn-filterform_titulo_para_renegociacao-baixa-titulos").on("click", function(){
            filterClearModalSelecionarTitulosRenegociacao();
            filterAjaxModalSelecionarTitulosRenegociacao();
        });

        form_titulo_para_renegociacao.find('#adicionar_renegociacao').off('click');
        form_titulo_para_renegociacao.find('#adicionar_renegociacao').on('click', function(){
            modalAdicionarNegociacao(form_titulo_para_renegociacao);
        });

        form_titulo_para_renegociacao.find('#selecione_todos').on('click', function(){
            if (form_titulo_para_renegociacao.find('#selecione_todos').is(':checked')){
                $('input:checkbox').prop("checked", true);
              }else{
                $('input:checkbox').prop("checked", false);
              }
        });

        form_titulo_para_renegociacao.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_titulo_para_renegociacao.find('.data').mask('00/00/0000');
    })

    function filterAjaxModalSelecionarTitulosRenegociacao(){
        form_titulo_para_renegociacao = $(document).find("#form_titulo_para_renegociacao_filter_titulos_para_renegociacao");
        data_form_titulo_para_renegociacao = form_titulo_para_renegociacao.serialize();
        filterClearModalSelecionarTitulosRenegociacao();
        $.ajax({
            url: '{{ route('aprovacao_renegociacao_titulo.filtro_selecionar_titulo')}}',
            data: data_form_titulo_para_renegociacao,
            method: 'POST',
            async: false,
            success: function(data){
                titulos = [];
                
                for (var fields in data.response){
                    temp_array = [
                        checkTitulo(data.response[fields]),
                        ajusteTamanhoTable(data.response[fields].estabelecimento_nome),
                        @if (count($cod_cliente)>1)
                        ajusteTamanhoTable(data.response[fields].nome_cliente),
                        @endif
                        data.response[fields].numero,
                        data.response[fields].parcela,
                        data.response[fields].data_emissao,
                        data.response[fields].data_vencimento,
                        data.response[fields].valor_original,
                        data.response[fields].juros_cobrados,
                        data.response[fields].valor,
                        data.response[fields].percentual_juros_diarios,
                        data.response[fields].data_juros,
                        data.response[fields].desconto,
                        data.response[fields].nota_numero,
                    ];
                    titulos.push(temp_array)
                }
                table_filters_dialog_titulos_renegociacao.rows.add(titulos).draw();            
    
            }
        });
    }
    
    function filterClearModalSelecionarTitulosRenegociacao(){
        table_filters_dialog_titulos_renegociacao.clear().draw();
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function modalAdicionarNegociacao(form_titulo_para_renegociacao){
        data_form_titulo_para_renegociacao = form_titulo_para_renegociacao.serialize()
        $title = 'Renegociação de Título'
        $.ajax({
            url: '{{ route('renegociacao_titulo.modal.adicionar') }}',
            type: 'post',
            data: data_form_titulo_para_renegociacao,
            success: function(callback){
                createModal("negociacao_titulo", $title, callback, '');
            },
            error: function(data) {
                message("Atenção", "Não foi selecionado nenhum título.");
            }
        });
    }

    function checkTitulo($value){
        html = '<input id="titulos_selecionado" name="titulos_selecionado[]" type="checkbox" value="'+$value.titulo_id+'" autocomplete="off">';

        return html;
    }
</script>
@endsection