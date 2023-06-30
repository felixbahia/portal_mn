@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_sugestao_compra_aprovacao" id="form_sugestao_compra_aprovacao" onsubmit="return false">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
                <div class="col-lg-2">
                  {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control input-label', 'placeholder' => 'Produto', 'maxlength' => '50']) }}
                </div>
                <div class="col-lg-2">
                    {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control input-label', 'placeholder' => 'Cliente', 'maxlength' => '50']) }}
                  </div>
                <div class="col-lg-2">
                    {{ Form::text('data_inicio','',['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
                </div>
                <div class="col-lg-2">
                    {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
                </div>
                <div class="col-lg-2">
                    <select name="status" id="status">
                        
                        @foreach($status_sugestao as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select name="gerentes" id="gerentes">
                        <option value="">Todos</option>
                        @foreach($gerentes as $key => $value)
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

    <table class="table table-striped" id="table-filters-sugestao_compra">
        <thead>
            <tr>
                <th>Foto</th>
                <th class="th-dados">Produto</th>
                <th class="th-dados">Composição</th>
                <th class="th-dados">Cliente</th>
                <th class="th-dados">Observação</th>
                <th class="th-dados">Emissão</th>
                <th class="th-dados">Status</th>
                <th class="th-dados">Usuário</th>
                <th class="tb_number">Volume</th>
                <th class="tb_number">Preço</th>
                <th>Reprovar</th>
                <th>Aprovar</th>
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

        table_filters_sugestao_compra = $('#table-filters-sugestao_compra')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
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

        table_filters_sugestao_compra.on('draw', function () {
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
            });
        });
    });

    function filtro(){
        table_filters_sugestao_compra.clear().draw();
        $.ajax({
            url: "{{ route('sugestao_compra.filtro') }}", 
            dataType: 'json',
            data: $(document).find('#form_sugestao_compra_aprovacao').serialize(),
            method: 'POST',
            success: function(callback){
                dados = callback.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            criarBtnFoto(dados[field]),
                            dados[field].produto_descricao,
                            dados[field].composicao,
                            dados[field].cliente_nome,
                            quebraTexto(dados[field].motivo),
                            dados[field].data_emissao,
                            dados[field].status,
                            dados[field].usuario,
                            dados[field].volume_produto,
                            dados[field].valor_estimado_venda,
                            createBtReprove(dados[field]),
                            createBtAprove(dados[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_sugestao_compra.rows.add(fields_filter).draw().nodes();
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Sugestão localizada.');
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

    function createBtAprove($dados){
        if ($dados.status === 'Aprovado' || $dados.status === 'Reprovado'){
            var html='';
        }else{
            
            var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"modalAprova('"+$dados.id+"')\"></div>";
        }
        return html;
    }

    function createBtReprove($dados){
        if ($dados.status === 'Reprovado' || $dados.status === 'Aprovado'){
            var html='';
           
         }else{
            var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" onclick=\"modalEditar('"+$dados.id+"')\"></div>";
          
        }
        return html;
        
    }
    function criarBtnFoto($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-view\" id=bt-foto data-toggle=\"tooltip\" data-placement=\"top\"></a>";
 
        return $html;
    }

    function quebraTexto($texto){
        if($texto == null){
             $html='';
         }else{
             
             var $html =   "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\"\" data-placement=\"left\" data-original-title=\""+ $texto +"\">\""+ $texto +"\"</div></div>";
         }
        
         return $html;
 
     }

    function aprovarSugestao($id){
        $.ajax({
            url: '{{ route("aprovacao_sugestao_compra.aprovar")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    filtro($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;

                    if(data.message.length > 0){
                        var resposta = data.message;
                    }
                    else{
                        var resposta = 'Houve uma instabilidade, tente novamente mais tarde!';
                    }
                    filterAjax($("#form_filter").serialize());
                    message('Atenção', resposta);
                }
            }
        });
    }

    function reprovarSugestao($id){
        $.ajax({
            url: '{{ route("aprovacao_sugestao_compra.reprovar")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    filtro($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', 'Ouve uma instabilidade, tente novamente mais tarde!');
                }
            }
        });
    }

    function modalEditar($id) {
        $.ajax({
            url: '{{ route("aprovacao_sugestao_compra.modal.recusa") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Motivo {{ CustomView::programaName() }}';
                createModal("modal_edit_sugestao_compra", title, body, '');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', 'Ouve uma instabilidade, tente novamente mais tarde!');
                }
            }
        });
    }

    function modalAprova($id) {
        $.ajax({
            url: '{{ route("aprovacao_sugestao_compra.modal.aprova") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Motivo {{ CustomView::programaName() }}';
                createModal("modal_edit_sugestao_compra", title, body, '');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', 'Ouve uma instabilidade, tente novamente mais tarde!');
                }
            }
        });
    }
    function modalFoto($id) {

        $.ajax({
            url: '{{ route("sugestao_compra.modal.foto") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){

                var title = 'Foto {{ CustomView::programaName() }}';
                createModal("modal_foto_sugestao_compra", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

@endsection