@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('perfil.update', ['id' => $dados["id"]]) }}" id="frm_edit" name="frm_edit" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('nome', 'Nome') }}
        {{ Form::text('nome', $dados["name"], array('class' => 'form-control')) }}
    </div>
    <div id="tree_permissoes">
        <ul>
            <li id='0'>Todos
            <ul>
            @foreach($permissoes as $permissoes)
                <li id="{{ $permissoes["modulos"]["permission_id"] }}" @if($permissoes["modulos"]["check"] === 1) class="jstree-checked" @endif>{{ $permissoes["modulos"]["nome"] }}<ul>
                    @foreach($permissoes["submodulos"] as $submodulo)
                        @if(isset($submodulo["permission_id"]))
                        <li id="{{ $submodulo["permission_id"] }}" @if($submodulo["check"] === 1) class="jstree-checked" @endif>{{ $submodulo["nome"] }}
                            @if(isset($submodulo["programas"]))
                            <ul>
                            @foreach($submodulo["programas"] as $programa)
                                <li id="{{ $programa["permission_id"] }}" @if($programa["check"] === 1) class="jstree-checked" @endif>{{ $programa["nome"] }}
                                    <ul>
                                        @foreach($programa['action'] as $action)
                                        <li id="{{ $action['permission_id'] }}" @if($action["check"] === 1) class="jstree-checked" @endif>{{ $action["nome"] }}</li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                                @if (isset($submodulo['submodulos']))
                                    @foreach($submodulo['submodulos'] as $submodulos)
                                    <li id="{{ $submodulos["permission_id"] }}" @if($programa["check"] === 1) class="jstree-checked" @endif>{{$submodulos["nome"]}}
                                        <ul>
                                            @foreach($submodulos['programas'] as $a)
                                            <li id="{{$a['permission_id']}}" @if($programa["check"] === 1) class="jstree-checked" @endif>{{ $a["nome"] }}
                                                <ul>
                                                    @foreach($a['action'] as $action)
                                                    <li id="{{$action['permission_id']}}" @if($action["check"] === 1) class="jstree-checked" @endif>{{ $action["nome"] }}</li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                    @endforeach
                                @endif
                            </ul>
                            @endif
                        </li>
                        @endif
                    @endforeach
                    @foreach($permissoes["programas"] as $programa)
                        <li id="{{ $programa["permission_id"] }}" @if($programa["check"] === 1) class="jstree-checked" @endif>{{ $programa["nome"] }}</li>
                    @endforeach
                    </ul>
                </li>
            @endforeach
        </ul></li>
    </ul>
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}

</form>
<script>
    $(function () {
        tree_permissoes = $('#tree_permissoes').jstree({
            "core" : {
                "animation" : 0,
                "themes" : {
                    "icons" : false
                }
            },
            "plugins" : [ "checkbox" ],
        });
        $('#tree_permissoes').jstree(true).select_node([@foreach($permissoes_programs as $value) '{{ $value }}', @endforeach]);
    });
</script>
@endsection