@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="multiselect col-lg-2">
                <div class="selectBox" onclick="showCheckboxes()">
                  {{ Form::select("estabelecimento", [], '', ["id" => "estabelecimento", "class"=>"form-control", "placeholder" => "Escolha o Estabelecimento      "]) }}
                  <div class="overSelect"></div>
                </div>
                <div id="checkboxes">
                    @foreach ($estabelecimentos as $key => $estabelecimento)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="{{ $key }}" id="{{ $key }}" value="{{ $estabelecimento }}" />
                            <label class="form-check-label" for="cambio"> {{ $estabelecimento }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-1">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
            </div>
            <div class="col-lg-1">
                <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="{{ date('d/m/Y', strtotime('+3 months')) }}" maxlength="20">
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="intercompany" id="intercompany" value="true" />
                    <label class="form-check-label" for="intercompany"> Intercompany</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="cambio" id="cambio" value="true" />
                    <label class="form-check-label" for="cambio"> Cambio</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="nacionalizacao" id="nacionalizacao" value="true" />
                    <label class="form-check-label" for="nacionalizacao"> Nacionalização</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="duvidosos" id="duvidosos" value="true" />
                    <label class="form-check-label" for="duvidosos"> Clientes duvidosos</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="juros" id="juros" value="true"/>
                    <label class="form-check-label" for="juros">Juros</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 estabelecimentos_label">
                {{ Form::label('', 'Estabelecimentos Selecionados:' ) }}
            </div>
            <div class="estabelecimentos_selecionados col-lg-12" id="verifica_estabelecimentos_vazio"></div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters2">
        <thead>
            <tr>
                <th class="tb_date sistema">data sistema</th>
                <th class="tb_date">Data</th>
                <th class="tb_number">A receber</th>
                <th class="tb_number">A pagar</th>
                <th class="tb_number">Saldo</th>
                <th class="tb_number">Saldo Acumulado</th>
                <th class="tb_number">Previsão a Pagar</th>
                <th class="tb_number">Saldo  Previsão</th>
            </tr>
        </thead>
        <tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tfoot>    
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready(function(){
        $(document).find('.estabelecimentos_label').hide();
        @foreach ($estabelecimentos as $key => $estabelecimento)
            $(document).find('#{{$key}}').mousedown(function() {

                if(!this.checked){
                    var conteudo = 
                    '<div class="col-lg-2 estabelecimentos {{ $key.'label_adicionado' }}">'+
                        '{{ Form::label('', $estabelecimento == '25 - IDARA SERVIÇOS ADMINISTRATIVOS EIRILI' ? '25 - IDARA' : $estabelecimento) }}'+
                    '</div>';
                    $(document).find('.estabelecimentos_selecionados').append( conteudo );
                }else{
                    $(document).find('.{{ $key.'label_adicionado' }}').remove();
                }
                
                if($(document).find('#verifica_estabelecimentos_vazio').html().length == 0){
                    $(document).find('.estabelecimentos_label').hide();
                }else{
                    $(document).find('.estabelecimentos_label').show();
                }
            });
        @endforeach
    });


    var expanded = false;

    table_filters_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": -1,
        "orderMulti": false,
        "ordering": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Fluxo de Caixa',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 1 || column === 2 || column === 3 || column === 4 || column === 5 || column === 6){
                                if(data != ''){
                                    numero = data.replaceAll('.','').replace(',','');
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "text_date", targets: "tb_date" },
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            {
                'targets': 'sistema',
                'visible': false
            }
            
        ],
        "order": [[ 0, 'asc' ]]
    };
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options);
    table_filters.draw();
    $(document).ready( function () {
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
        
        $("#btn-filterform").on("click", function(){
            filtro($("#form_filter").serialize());
        });
    });

    function filtro(data_form){
        var $return;

        $(document).find("#checkboxes").hide();

        var form = $(document).find("#form_filter");

        $(document).find('.error-message').remove();
        $(document).find('.error-input').removeClass('error-input');

        $(table_filters.column(1).footer()).html('');
        $(table_filters.column(2).footer()).html('');
        $(table_filters.column(3).footer()).html('');
        table_filters.draw();

        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('fluxo_caixa.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status == 'success'){
                    var data = callback.response.saida;
                    if(Object.keys(data).length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                field,
                                data[field].data,
                                data[field].valor_a_receber,
                                data[field].valor_a_pagar,
                                data[field].valor_saldo,
                                data[field].valor_saldo_acumulado,
                                data[field].valor_previsao,
                                data[field].valor_previsao_acumulado,
                            ];
                            fields_filter.push(temp_field);
                        }
                        $(table_filters.column(1).footer()).html((callback.response.total.valor_a_receber) ? 'TOTAL' : '');
                        $(table_filters.column(2).footer()).html(callback.response.total.valor_a_receber);
                        $(table_filters.column(3).footer()).html(callback.response.total.valor_a_pagar);
                        table_filters.rows.add(fields_filter).draw().nodes();
                    }
                }
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }
    
    function openDadosFluxo($criterios, $titulo){
        $.ajax({
            url: '{{ route('fluxo_caixa.modal.dados') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', criterios: $criterios },
            success: function(body){
                createModal('fluxo_de_caixa_dados', $titulo, body, 'modal-lg');
            }
        })
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    
    function showCheckboxes() {
        var checkboxes = document.getElementById("checkboxes");
        if (!expanded) {
            checkboxes.style.display = "block";
            expanded = true;
        } else {
            checkboxes.style.display = "none";
            expanded = false;
        }
    }

    function openPrevisaoFluxo($criterios, $titulo){
        $.ajax({
            url: '{{ route('fluxo_caixa.modal.previsao') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', criterios: $criterios },
            success: function(body){
                createModal('fluxo_de_caixa_dados_previsao', $titulo, body, 'modal-lg');
            }
        })
    }
@endsection
