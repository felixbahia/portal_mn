@extends('layouts.app')

@section('content-filter')
  <form action="{{ route("upload_ramais.filter") }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div>
        <div>
            <div>Para enviar seu arquivo, será necessário que o arquivo esteja em extensão txt, e esteja nessa estrutura<br>
                 Departamento;Funcionário;Ramal<br><br>         
               
               
                <input type="file" name="arquivo">
                <br><br><br>
                            
            </div>
            <div><h3>{{ $return }}</h3></div>
        </div>
    </div>
    <div class="content-buttons">
        <button type="submit" name="btn-filterform" id="btn-filterform" class="btn-filter">Enviar</button>
    </div>
</form>



@endsection
@section("script-footer")

$(document).ready(function(){

    .file-upload { display:none; }

    $('.data').mask('00/00/0000');

    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        endDate: new Date(),
        zIndex: 100,
        autoHide: true
    });

 @endsection