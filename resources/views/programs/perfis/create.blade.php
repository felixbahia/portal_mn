@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('perfil.store') }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('nome', 'Nome') }}
        {{ Form::text('nome', '', array('class' => 'form-control')) }}
    </div>
    <div id="tree_permissoes">
        <ul>
            <li id=''>Todos
            <ul>
            @foreach($permissoes as $permissoes)
                <li id="{{ $permissoes["modulos"]["permission_id"] }}">{{ $permissoes["modulos"]["nome"] }}<ul>
                    @foreach($permissoes["submodulos"] as $submodulo)
                        <li id="{{ $submodulo["permission_id"] }}">{{ $submodulo["nome"] }}<ul>
                            @foreach($submodulo["programas"] as $programa)
                                <li id="{{ $programa["permission_id"] }}">{{ $programa["nome"] }}
                                    <ul>
                                        @foreach($programa['action'] as $action)
                                        <li id="{{ $action['permission_id'] }}">{{ $action["nome"] }}</li>
                                        @endforeach
                                    </ul>
								</li>
                            @endforeach
                            @if (isset($submodulo['submodulos']))
                                @foreach($submodulo['submodulos'] as $submodulos)
                                <li id="{{ $submodulos["permission_id"] }}">{{$submodulos["nome"]}}
                                    <ul>
                                        @foreach($submodulos['programas'] as $a)
                                        <li id="{{ $a['permission_id'] }}">{{ $a["nome"] }}</li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            @endif
                            </ul>
                        </li>
                    @endforeach
                    @foreach($permissoes["programas"] as $programa)
                        <li id="{{ $programa["permission_id"] }}">{{ $programa["nome"] }}</li>
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
    });
</script>
@endsection
