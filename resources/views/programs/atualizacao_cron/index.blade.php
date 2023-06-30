@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_atualizacao_cron" id="form_atualizacao_cron" onsubmit="return false">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
                <div class="col-lg-2">
                  {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control input-label', 'placeholder' => 'Descrição', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-2">
                    {{ Form::text('data_inicio','',['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
                </div>
                <div class="col-lg-2">
                    {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
                </div>
                <div class="col-lg-2">
                    <select name="status" id="status">
                        <option value="">Todos</option>
                        @foreach($status as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                      
                    </select>
                </div>
                
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

    <table class="table table-striped" id="table-filters-atualizacao_cron">
        <thead>
            <tr>
                <th class="th-dados">Descrição</th>
                <th class="tb_date"> Início</th>
                <th class="tb_number">Minutos</th>
                <th class="th-dados">Status</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready(function(){
        $(document).find('#btn-filterform').on('click', function(event){
            event.stopPropagation();
            filtro();
        });
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
        $(document).find("#btn-create").off("click");
        $(document).find("#btn-create").on("click", function(){
            event.stopPropagation();
          
            modalAdicionar();
         
        });
        table_filters_atualizacao_cron = $('#table-filters-atualizacao_cron')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 200,
            "autoWidth": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ]
        });

        table_filters_atualizacao_cron.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(){
                modalEditar($(this).data('id'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(){
                modalDeletar($(this).data('id'));
            });
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(){
                modalFoto($(this).data('id'));
            })
        });
    });

    function filtro(){
        table_filters_atualizacao_cron.clear().draw();
        $.ajax({
            url: "{{ route('atualizacao_cron.filtro') }}", 
            dataType: 'json',
            data: $(document).find('#form_atualizacao_cron').serialize(),
            method: 'POST',
            success: function(callback){
                dados = callback.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [

 
                            dados[field].descricao,
                           
                            dados[field].inicio_atualizacao,
                           dados[field].tempo,
                            dados[field].status,
                            
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_atualizacao_cron.rows.add(fields_filter).draw().nodes();
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhum Processo Cron localizado.');
            }
        });
    }


    function carregarData(){
        var d = new Date();
        var anoC = d.getFullYear();
        var mesC = d.getMonth();
    
        var d1 = new Date (anoC, mesC, 1);
        var d2 = new Date (anoC, mesC+1, 0);
        $('#data_inicio').val(dataAtualFormatada(d1));
        $('#data_fim').val(dataAtualFormatada(d2));
    }
    function dataAtualFormatada(data){
            dia  = data.getDate().toString().padStart(2, '0'),
            mes  = (data.getMonth()+1).toString().padStart(2, '0'),
            ano  = data.getFullYear();
        return dia+"/"+mes+"/"+ano;
    }
@endsection