@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="{{ CustomView::retornaClientePadraoNomeSemCNPJ() }}" placeholder="Nome / Razão Social" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome_guerra" id="nome_guerra" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="cnpj_cpf" id="cnpj_cpf" value="" placeholder="CNPJ / CPF" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="data_inicio" id="data_inicio" class="data" value="" placeholder="Última venda de" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="data_fim" id="data_fim" class="data" value="" placeholder="Última venda até" maxlength="250" />
        </div>
        <div class="col-lg-2">
           {{ Form::select('estados', $estados, '', ['id' => 'estados', 'class' => 'form-control', 'placeholder' => 'Estados']) }}
           
        </div>
        <div class="col-lg-2">
            {{ Form::select('cidades', $cidades, '', ['id' => 'cidades', 'class' => 'form-control', 'placeholder' => 'Cidades']) }}
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class='number_format'>Código</th>
                <th>Nome / Razão Social</th>
                <th>Nome Fantasia / Apelido</th>
                <th>CNPJ / CPF</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th class='date_format'>Última venda</th>
                <th>Gerente</th>
                <th class='date_format'>Bloqueado</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')
<div class="modal fade" id="model_cliente_view" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Visualizar {{ CustomView::programaName() }} <span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $("#estados").change(function(){
            filterCidades();
        });
        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
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
                    }
                },
            ],
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
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
            ],
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-invoice-dollar").off("click");
            $(document).find(".bt-invoice-dollar").on("click", function(event){
                showModalAnaliseSintetica($(this).data('hash'));
            });
        });
        $(".btn-clear").on("click", function(){
            $form = $(this).parents('form');
            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input, select').not('[class^=btn-]').not('[name=_token]').val('');
                }
            });
            
        });
    });

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        endDate: new Date(),
        zIndex: 100,
        autoHide: true
    });

    function showModal($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        $("#model_cliente_view").modal("toggle");
        $("#model_cliente_view").off('shown.bs.modal');
        $("#model_cliente_view").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", codcad: id},
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);
                }
            });
        });
        $("#model_cliente_view").off('hidden.bs.modal');
        $("#model_cliente_view").on('hidden.bs.modal', function (e) {
            $("#model_cliente_view").find('.modal-body').html('');
        });
    }

    function showModalAnaliseSintetica($hash){
        $.ajax({
            url: '{{ route('cliente.posicao_sintetica.modal') }}',
            data: {_token: "{{ csrf_token() }}", id_encriptada: $hash},
            method: 'POST',
            success: function(data){
                createModal('analise_sintetica_modal', 'Análise sintética do cliente', data, 'modal-lg');
            }
        });
    }

    function createBtnView($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes do cliente\"></a>";
        return $html;
    }
    function createBtnAnaliseSintetica($url, $hash){
        var $html = "<a href=\"#\" data-hash=\""+$hash+"\" class=\"bt-invoice-dollar\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Análise sintética\"></a>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('cliente.filter_consulta') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(dados){

                data = dados.response;

                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codcad,
                            ajusteTamanhoTable(data[field].nome),
                            ajusteTamanhoTable(data[field].guerra),
                            data[field].cgc_cpf,
                            data[field].cidade,
                            data[field].estado,
                            data[field].ultima_venda,
                            ajusteTamanhoTable(data[field].gerente),
                            errorMessage(data[field].bloqueado),
                            createBtnView("{{ route('cliente.view') }}", data[field].codcad),
                            createBtnAnaliseSintetica("{{  route('cliente.posicao_sintetica.modal') }}", data[field].hash),
                            createBtnViewContrato(data[field].contrato),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                    $(document).find(".bt-view").off("click");
                    $(document).find(".bt-view").on("click", function(event){
                        event.stopPropagation();
                        showModal($(this));
                    });
                    $(document).find(".bt-invoice-dollar").off("click");
                    $(document).find(".bt-invoice-dollar").on("click", function(event){
                        showModalAnaliseSintetica($(this).data('hash'));
                    });
                }
            }
        });
    }

    function createBtnViewContrato($value){
        html = "";
        if($value !== ""){
            html = "<a href=\""+$value+"\" class=\"btn-pedido\" title=\"Contrato\" target=\"_blank\"></a>";
        }

        return html;
    }
    
    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function errorMessage($msg){
        if($msg != ''){
            var html = "<span class='error-message'>" + $msg + "</span>";
        }
        else{
            var html = "";
        }

        return html;
    }

    function filterCidades(){
        var uf =   $(document).find('#estados').val();

          
          $.ajax({
           
              url: "{{ route('cliente.cidades') }}",
              method: 'POST',
         
              data: {
                  _token: '{{csrf_token()}}',
                  estados: uf
  
              },
              success: function(dados){
                $(document).find('#cidades').empty();
                $(document).find('#cidades').append('<option value="">Cidades</option>');
              var data=  dados.response;
          

                    var fields_filter = [];
                    for(var field in data){
                     
                       data[field];
                       $(document).find('#cidades').append('<option value=' + data[field] +'>' + data[field] +'</option>');
                    }
                
              },
              error: function (callback){
                  if((callback.responseJSON)){
                      var dados = callback.responseJSON;
                      var mensagem;
                    
                      
                      for(var field in dados){
                           if(dados[field].length >3){
                              mensagem =dados[field];
                          }
                      }
                      message('Erro ao Confirmar ',mensagem+'.<br />');
                  
                  }
              }
          });


    }
@endsection