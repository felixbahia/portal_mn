@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
	        <select name="empresa" id="empresa">
	            <option value=''>Empresa</option>
	            @foreach(returnEmpresasPrologusView() as $key => $value)
	            <option value="{{ $key }}">{{ $value }}</option>
	            @endforeach
	        </select>
        </div>
        <div class="col-lg-2">
            <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="produto" id="produto" value="" placeholder="Código de produto" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome Produto" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-margems">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Grupo</th>
                <th>Produto</th>
                <th>Nome</th>
                <th>Marca</th>
                <th>Linha</th>
				<th>Margem %</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')

{{-- Modal de adição --}}
<div class="modal fade" id="modal_margem" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>

{{-- Modal de edição --}}
<div class="modal fade" id="modal_margem_edit" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>

{{-- Modal de exclusão --}}
<div class="modal fade" id="modal_margem_delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')