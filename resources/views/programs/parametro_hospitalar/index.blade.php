@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div class="content-fields">
    </div>
    <div class="content-buttons">
        @if(empty($dados))
        <button name="btn-create" id="btn-create-adicionar" class="btn-create">Adicionar</button>
        @else
        <button name="btn-create" id="btn-create-editar" class="btn-create">Editar</button>
        @endif
    </div>
</form>
@endsection
@section('script-footer')
$(document).ready( function () {
    $("#btn-create-adicionar").on("click", function(){
        showModalCreate();
    });
    $("#btn-create-editar").on("click", function(){
        showModalEditar();
    });
});

function showModalCreate(){
    $.ajax({
        url: '{{ route('parametro_hospitalar.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_adicionar', title, body, '');
        }
    });
}

function showModalEditar(){
    $.ajax({
        url: '{{ route('parametro_hospitalar.modal.editar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_editar', title, body, '');
        }
    });
}
@endsection