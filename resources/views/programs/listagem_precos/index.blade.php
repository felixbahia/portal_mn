@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            
            <div class="filtro-linha">
                <div id="row">
                    <div class="col-sm-12">
                        <span id="filtros-show-param"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="tooltext-click-precos">Clique aqui para mostrar o filtro</div>
                        <hr>
                    </div>
                </div>
            </div>

            <div class="filtro-campos">
                <div class="row">
                    <div class="col-sm-2">
                        <select name="origem" id="origem">
                            <option value='' selected>Origem</option>
                            @foreach($origem as $key => $value)
                            <option value="{{$key}}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="estado" id="estado">
                            <option disabled selected>Selecione um estabelecimento</option>
                        </select>
                    </div>

                    @if (Auth::user()->tipo_usuario_id == 16)
                        <input type="hidden" name="coluna" id="coluna" value="coluna_a">
                    @else
                    <div class="col-sm-2">
                        <select name="coluna" id="coluna">
                            <option value="coluna_a">Comissão A</option>
                            <option value="coluna_b">Comissão B</option>
                            <option value="coluna_c">Comissão C</option>
                            <option value="prazo_vista">À vista</option>
                            <option value="prazo_15">Prazo 15 dias</option>
                            <option value="prazo_30">Prazo 30 dias</option>
                            <option value="prazo_45">Prazo 45 dias</option>
                            <option value="prazo_60">Prazo 60 dias</option>
                            <option value="prazo_75">Prazo 75 dias</option>
                            <option value="prazo_90">Prazo 90 dias</option>
                        </select>
                    </div>
                    @endif

                    <div class="col-sm-2 d-none">
                        <select name="moeda" id="moeda">
                            <option value=''>Moeda</option>
                            <option value="real" selected>Real</option>
                            <option value="dolar">Dólar</option>
                        </select>
                    </div>

                    <div class="col-sm-2">
                        <select name="frete" id="frete">
                            <option value=''>Frete</option>
                            <option value="fob">FOB</option>
                            <option value="cif">CIF</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="tipo_cliente" id="tipo_cliente">
                            <option value="normal">Contribuinte</option>
                            <option value="isento">Isento</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-sm-2">
                    <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
                </div>
                <div class="col-sm-2">
                    <input type="text" name="nome" id="nome" value="" placeholder="Nome Produto" maxlength="250" />
                </div>
                <div class="col-sm-2">
                    <input type="text" name="produto" id="produto" value="" placeholder="Código de produto" maxlength="250" />
                </div>
                <div class="col-sm-2">
                    <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
                </div>
                <div class="col-sm-2">
                    <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
                </div>
                <div class="col-sm-2">
                    <input type="hidden" name="tem_estoque" id="tem_estoque" value="{{$tem_estoque}}" />
                </div>
            </div>

        </div>

    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />

        <button name="btn-download" id="btn-download" class="btn-pdf float-right" onclick='download()'>Exportação da lista completa</button>
    </div>

</form>
@endsection

@section('content')
<div class="content-table">
    {{-- <div class="dt-buttons btn-group"><button class="btn btn-secondary buttons-excel buttons-html5" tabindex="0" aria-controls="table-filters-produtos" type="button"><span> </span></button> </div> --}}
    <table class="table table-striped" id="table-filters-precos">
        <thead>
            <tr>
                {{-- <th rowspan='2'>Marca</th> --}}
                <th class='grupo' rowspan='2'>Grupo</th>
                <th class='linha' rowspan='2'>Linha</th>
                <th class='nome' rowspan='2'>Nome</th>
                <th class='cod_produto' rowspan='2'>Cód. Prod.</th>
                <th class='gramatura' rowspan='2'>GML</th>
                <th class='largura' rowspan='2'>Larg.</th>
                <th class='unidade' rowspan='2'>Un.</th>
                @if (Auth::user()->tipo_usuario_id != 16)
                <th colspan='3' class='text-center'>Comissão</th>
                @endif
                <th colspan='7' class='text-center'>Prazos</th>
            </tr>
            <tr>
                @if (Auth::user()->tipo_usuario_id != 16)
                <th class='comissoes' id="coluna_a">A</th>
                <th class='comissoes' id="coluna_b">B</th>
                <th class='comissoes' id="coluna_c">C</th>
                @endif
                <th class='prazo' id="prazo_vista">Vista</th>
                <th class='prazo' id="prazo_15">15</th>
                <th class='prazo' id="prazo_30">30</th>
                <th class='prazo' id="prazo_45">45</th>
                <th class='prazo' id="prazo_60">60</th>
                <th class='prazo' id="prazo_75">75</th>
                <th class='prazo' id="prazo_90">90</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {

        $("#coluna").on("change",function(){
            table_filters.clear().draw();
            $(".buttons-excel").hide();
            $('[data-toggle="tooltip"]').tooltip('hide');
        });

        mostraFiltros();

        $(".tooltext-click-precos").on("click", function(){
            mostraFiltros();
        })

        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));

        $("#origem").on("change", function(event){
            table_filters.clear().draw();
            $(".buttons-excel").hide();
            $('[data-toggle="tooltip"]').tooltip('hide');

            selectEstado($(this).val());

            if ($('#origem').val() == 'RO'){
                
                $('#frete').val('cif');
                $('#moeda').parent().removeClass('d-none');

            }
            else if ($('#origem').val() == 'TO'){

                $('#moeda').val('real');
                $('#moeda').parent().addClass('d-none');

            }
            else {

                $('#moeda').parent().addClass('d-none');
                $('#moeda').val('real');

            }
            
            $("#estado").trigger('change');
        });

        $("#estado").on('change', function(){
            $('#regiao').val($("#estado").find("option:selected").data('regiao'));
        })

        @foreach($campos_salvos as $campo => $valor)
            @if($campo == 'coluna')
                @if (Auth::user()->tipo_usuario_id != 16)
                    $("[name='{{ $campo }}']").val("{{ $valor }}");
                @endif
            @else
                $("[name='{{ $campo }}']").val("{{ $valor }}");
            @endif
        @endforeach

        $("#origem").trigger('change');
        
        setTimeout(function(){
            $("#estado").trigger('change');
        }, 500);

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        table_filters.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip('hide');
            $('[data-toggle="tooltip"]').tooltip();
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

        $(".buttons-excel").hide();
        $(".buttons-excel").on("click", function(){

        });
        $('[data-toggle="tooltip"]').tooltip('hide');

        $("#nova_busca_link").on('click', function(){
            mostraFiltros();
        });

	});

    function optionsAutoComplete($name, element = null){

        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
        	},
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    table_filters = $('#table-filters-precos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
                                if(column >= 7){
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
            {
                extend: 'pdfHtml5',
                text: ' ',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    modifier: {
                        page: 'all'
                    },
                },
                orientation: 'landscape',
                pageSize: 'LEGAL'
            },
        ],
        "columnDefs": [
            {
                @if (Auth::user()->tipo_usuario_id != 16)
                "targets": ['comissoes', 'prazo'],
                @else
                "targets": 'prazo',
                @endif
                "className": 'number_format',
                "width": "10% !important",
                @if (Auth::user()->tipo_usuario_id != 16)
                "visible": false
                @endif
            },
            {
                'targets': ['gramatura', 'largura', 'unidade'],
                'width': '5px'
            },
            {
                'targets': ['gramatura', 'largura'],
                "className": "number_format"
            },
            {
                'targets': 'nome',
                'width': '20%'
            },
            {
                'targets': 'grupo',
                'className': 'text-center grupo'
            }
        ],
        'rowsGroup': [
            '.grupo',
            '.linha'
        ],
        "order": [[ 0, 'asc' ], [1, 'asc']]
    });

    function showErrorsInputs(form, input, message){
        
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

    }

    function selectEstado(origem){

        $("#estado").find('option').remove();

        if (origem.length > 0){

            $.ajax({
                url: "{{ route('listagem_precos.aliquotas') }}",
                dataType: 'json',
                data: {_token: "{{ csrf_token() }}", origem: origem},
                method: 'POST',
                success: function(data){
                    $("#estado").append("<option value='' selected>Destino</option>");

                    for (var i in data){
                        $("#estado").append("<option value='"+data[i].value+"' data-regiao='"+ data[i].regiao +"'>" + data[i].html + "</option>");
                    }
                    @if(isset($campos_salvos['estado']))
                    $("#estado").val("{{$campos_salvos['estado']}}");
                    @else
                    $("#estado").val('');
                    @endif
                },
                error: function(data){
                    $("#aliquota").append('<option>Erro</option>').prop('disabled selected');
                }
            });
        }
        else{
            $("#aliquota").append("<option value=''>Selecione origem</option>");            
        }

    }

    function filterAjax(data_form){
        var $return;	    
        table_filters.clear().draw();
        $(".buttons-excel").hide();
        $('[data-toggle="tooltip"]').tooltip('hide');
	    var form = $("#form_filter");

        form.find('.error-message').remove();
        form.find('input, select').removeClass('error-input');

        $.ajax({
            url: "{{ route('listagem_precos.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                    
                var linhas = data;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in data){

                        var temp_field = [
                            // "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].marca+"\">"+linhas[field].marca+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].grupo+"\">"+linhas[field].grupo+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].linha+"\">"+linhas[field].linha+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].nome+"\">"+linhas[field].nome+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].cod_produto+"\">"+linhas[field].cod_produto+"</div></div>",
                            linhas[field].gramatura,
                            linhas[field].largura,
                            linhas[field].unidade,
                            @if (Auth::user()->tipo_usuario_id != 16)
                            linhas[field].coluna_a,
                            linhas[field].coluna_b,
                            linhas[field].coluna_c,
                            @endif
                            linhas[field].prazo_vista,
                            linhas[field].prazo_15,
                            linhas[field].prazo_30,
                            linhas[field].prazo_45,
                            linhas[field].prazo_60,
                            linhas[field].prazo_75,
                            linhas[field].prazo_90,
                        ];

                        fields_filter.push(temp_field);

                    }
                    $(".buttons-excel").show();


                    var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                    var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60", 'prazo_75', 'prazo_90');

                    @if (Auth::user()->tipo_usuario_id != 16)
                    table_filters.columns('.comissoes').visible(false);
                    table_filters.columns('.prazo').visible(false);

                    if(coluna.indexOf($("#coluna").val()) != -1){

                        table_filters.columns('.comissoes').visible(false);
                        table_filters.columns('.prazo').visible(true);

                    }
                    else if (prazo.indexOf($("#coluna").val()) != -1){

                        table_filters.columns('.prazo').visible(false);
                        table_filters.columns('.comissoes').visible(true);

                    }
                    @endif
                    table_filters.rows.add(fields_filter).draw();
                    table_filters.columns.adjust().draw();

                    var filtro = [];

                    if($(".filtro-linha").is(":hidden")){
                        form.find('.filtro-campos').find("select:visible, input:visible").each(function() {
                            if ($(this).val().length > 0 && $(this).attr('name') != "_token" && $(this).attr('name') != "btn-clearform"){

                                var valor;
                                @if (Auth::user()->tipo_usuario_id == 16)
                                if($(this).attr('name') != 'coluna'){
                                @endif
                                    if($(this).prop('tagName') == "SELECT"){
                                        valor = $(this).find(":checked").text();
                                    }
                                    else{
                                        valor = $(this).val();
                                    }

                                    filtro_linha =  "<b>" + $(this).attr('name') + ":</b> \"" + valor + "\"";
                                    filtro.push(filtro_linha);
                                @if (Auth::user()->tipo_usuario_id == 16)
                                }
                                @endif
                            }
        
                        });
                        $("#filtros-show-param").html(filtro.join(' - '));
                        // escondeFiltros();
                    }


                }
                $('[data-toggle="tooltip"]').tooltip();
            },
            error: function(data){
                hide_loader();
                if((data.responseJSON.errors)){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
                }
            }
        });
    }

    function escondeFiltros(){
        $(".filtro-campos").slideUp('slow');
        $(".filtro-linha").slideDown('fast');

        $(".content-fields").animate("slow");
        $(".tooltext-click-precos").fadeIn('slow');
    }

    function mostraFiltros(){

        $(".filtro-campos").slideDown('slow');
        $(".filtro-linha").slideUp('fast');
        $(".tooltext-click-precos").fadeOut('slow');

    }

    function download(){

        var form = '#form_filter';
        var msg_erro = new Object;

        $(form).find('.error-message').remove();
        $(form).find('input, select').removeClass('error-input');
        
        if($("#origem").val() == ''){
            msg_erro['origem'] = 'Selecione a origem';
        }

        if($("#estado").val() == '' || $("#estado").val() == null){
            msg_erro['estado'] = 'Selecione o estado onde se localiza o cliente';
        }

        if($("#frete").val() == ''){
            msg_erro['frete'] = 'Selecione a categoria de frete';
        }

        if($("#moeda").val() == '' && $("#origem").val() == 'RO') {
            msg_erro['moeda'] = 'Selecione a moeda';
        }

        if($("#coluna").val().substring(0,5) == 'prazo'){
            msg_erro['coluna'] = 'Não há exportações por prazo';
        }

        if(Object.keys(msg_erro).length > 0){
            for(var field in msg_erro){
                showErrorsInputs(form, field, msg_erro[field]);
            }
        }
        else{
            $('<form action="{{ route('listagem_precos.download') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="origem" value="'+$("#origem").val()+'">\
                <input type="hidden" name="estado" value="'+$("#estado").val()+'" />\
                <input type="hidden" name="coluna" value="'+$("#coluna").val()+'"  />\
                <input type="hidden" name="frete" value="'+$("#frete").val()+'" />\
                <input type="hidden" name="moeda" value="'+$("#moeda").val()+'" />\
                <input type="hidden" name="tipo_cliente" value="'+$("#tipo_cliente").val()+'" />\
            </form>').appendTo('body').submit().remove();
        }

    }
@endsection