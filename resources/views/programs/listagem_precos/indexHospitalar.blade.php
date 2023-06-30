@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="container filtro-campos" >

                <div class="row">
                    <div class="col-lg-4">
                        <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
                    </div>
                    <div class="col-lg-4">
                        <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
                    </div>
                    <div class="col-lg-4">
                        <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
                    </div>
                </div>
                
                <div class="row filtro-campos">
                    <div class="col-lg-3">
                        <input type="text" name="produto" id="produto" value="" placeholder="Código de produto" maxlength="250" />
                    </div>
                    <div class="col-lg-5">
                        <input type="text" name="nome" id="nome" value="" placeholder="Nome Produto" maxlength="250" />
                    </div>
                </div>

                <div class="row">
                    <hr class="col-lg-12">
                </div>

            </div>
 
            <div class="container filtro-campos">
                <div class="row">
                    <div class="col-lg-2">
                        <select name="origem" id="origem">
                            <option value='' selected>Origem</option>
                            @foreach($origem as $key => $value)
                            <option value="{{$key}}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <select name="coluna" id="coluna">
                            <option value=''>Coluna</option>
                            <option value="tecidos">Tecidos</option>
                            <option value="producao">Produção</option>
                        </select>
                    </div>

                </div>
            </div> 

            <div class="container filtro-linha">
                <div id="row">
                    <div class="col-lg-12" id="filtros-show-param">
                    </div>
                </div>
            </div>

        </div>

    <div class="content-buttons filtro-campos">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>

</form>
<div class="tooltext-click">Clique aqui para mostrar o filtro</div>
@endsection

@section('content')
<div class="content-table">
    <div class="dt-buttons btn-group"><button class="btn btn-secondary buttons-excel buttons-html5" tabindex="0" aria-controls="table-filters-produtos" type="button"><span> </span></button> </div>
    <table class="table table-striped" id="table-filters-precos">
        <thead>
            <tr>
                <th>Marca</th>
                <th>Linha</th>
                <th>Grupo</th>
                <th>Cód. Produto</th>
                <th>Nome</th>
                <th>Custo Estimado</th>
                <th>Valor</th>
                <th>Sul / Sudeste</th>
                <th>Centro-Oeste</th>
                <th>Norte / Nordeste</th>
                <th>GML</th>
                <th>Larg.</th>
                <th>Un.</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

    $(document).ready( function () {


        $("#coluna").on("change",function(){
            table_filters.clear().draw();
            $(".buttons-excel").hide();
            $('[data-toggle="tooltip"]').tooltip('hide');
        });

        mostraFiltros();

        $(".tooltext-click").on("click", function(){
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
        });

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

        $("#hospitalar").trigger('change');

        $(".buttons-excel").hide();
        $(".buttons-excel").on("click", function(){
            $('<form action="{{ route('listagem_precos.hospitalar.export') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="origem" value="'+$("#origem").val()+'">\
                <input type="hidden" name="grupo" value="'+$("#grupo").val()+'" />\
                <input type="hidden" name="produto" value="'+$("#produto").val()+'"  />\
                <input type="hidden" name="nome" value="'+$("#nome").val()+'" />\
                <input type="hidden" name="marca" value="'+$("#marca").val()+'" />\
                <input type="hidden" name="linha" value="'+$("#linha").val()+'" />\
                <input type="hidden" name="frete" value="'+$("#frete").val()+'" />\
            </form>').appendTo('body').submit().remove();
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
        "orderMulti": false,
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
        "columnDefs": [
            {
                "targets": [5,6,7,8,9,10],

                "className": 'number_format',
                "width": "10%"

            },
            {
                "targets": 3,
                "width": "10%"
            },
            {
                "targets": 4,
                "width": "20%"
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function showErrorsInputs(form, input, message){
        
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
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
            url: "{{ route('listagem_precos.hospitalar.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                    
                var linhas = data;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in data){

                        var temp_field = [
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].marca+"\">"+linhas[field].marca+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].linha+"\">"+linhas[field].linha+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].grupo+"\">"+linhas[field].grupo+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].cod_produto+"\">"+linhas[field].cod_produto+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].nome+"\">"+linhas[field].nome+"</div></div>",
                            linhas[field].custo_estimado,
                            linhas[field].preco,
                            linhas[field].sse,
                            linhas[field].co,
                            linhas[field].nne,
                            linhas[field].gramatura,
                            linhas[field].largura,
                            linhas[field].unidade,
                        ];

                        fields_filter.push(temp_field);

                    }
                    $(".buttons-excel").show();

                    table_filters.rows.add(fields_filter).draw().nodes();

                    var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                    var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60");

                    var filtro = [];

                    form.find("select, input").each(function() {
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
                    escondeFiltros();

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

        $(".content-fields").animate({height: "0%"}, "slow");
        $(".tooltext-click").fadeIn('slow');
    }

    function mostraFiltros(){

        $(".content-fields").css("height", "auto");
        $(".filtro-campos").slideDown('slow');
        $(".filtro-linha").slideUp('fast');
        $(".tooltext-click").fadeOut('slow');

    }
@endsection
</script>