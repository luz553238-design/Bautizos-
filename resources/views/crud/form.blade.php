@extends('crud.layout')
@section('title',($row ? 'Editar · ' : 'Crear · ').$meta['title'])
@section('content')
<a class="text-secondary small" href="{{ route('crud.index',$module) }}">← Volver a {{ strtolower($meta['title']) }}</a><h1 class="brand mt-3">{{ $row ? 'Editar registro' : 'Crear registro' }}</h1><p class="text-secondary">{{ $meta['title'] }} · Los campos con * son obligatorios.</p>
<form method="post" action="{{ $row ? route('crud.update',[$module,$record]) : route('crud.store',$module) }}" class="card p-4 mt-4">@csrf @if($row)@method('PUT')@endif<div class="row g-4">
@foreach($meta['columns'] as $field=>$definition)
@continue($definition['auto'] || $definition['generated'])
@php($value=old($field,$row ? $row->$field : $definition['default']))
@php($required=$definition['required'] && !($row && $field==='contrasena'))
<div class="{{ $definition['type']==='text' ? 'col-12' : 'col-md-6' }}"><label for="{{ $field }}" class="form-label">{{ \App\Http\Controllers\CrudController::label($field) }} {{ $required ? '*' : '' }}</label>
@if(isset($options[$field]))
<select id="{{ $field }}" name="{{ $field }}" class="form-select" @required($required)><option value="">Selecciona una opción</option>@foreach($options[$field] as $id=>$label)<option value="{{ $id }}" @selected((string)$value===(string)$id)>{{ $label }}</option>@endforeach</select>
@if(!$options[$field])<small class="text-secondary">Primero registra datos en <a href="{{ route('crud.create',$meta['foreign'][$field]['table']) }}">{{ $modules[$meta['foreign'][$field]['table']]['title'] }}</a>.</small>@endif
@elseif($definition['enum'] || $definition['type']==='boolean')
@php($choices=$definition['type']==='boolean' ? [1=>'Sí',0=>'No'] : array_combine($definition['enum'],$definition['enum']))
<select id="{{ $field }}" name="{{ $field }}" class="form-select" @required($required)>@if(!$required)<option value="">Sin especificar</option>@endif @foreach($choices as $id=>$label)<option value="{{ $id }}" @selected($definition['type']==='boolean' ? (int)$value===(int)$id : (string)$value===(string)$id)>{{ ucfirst($label) }}</option>@endforeach</select>
@elseif($definition['type']==='text')
<textarea class="form-control" rows="4" id="{{ $field }}" name="{{ $field }}" @required($required)>{{ $value }}</textarea>
@else
@php($type=$field==='contrasena' ? 'password' : ($field==='correo' ? 'email' : match($definition['type']) {'int','bigint'=>'number','datetime'=>'datetime-local','date'=>'date','time'=>'time',default=>'text'}))
@php($inputValue=$field==='contrasena' ? '' : ($type==='time' && $value ? substr($value,0,5) : ($type==='datetime-local' && $value ? str_replace(' ','T',substr($value,0,16)) : $value)))
<input class="form-control" type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ $inputValue }}" @required($required) @if($definition['length']) maxlength="{{ $definition['length'] }}" @endif @if($type==='number') min="{{ $field==='capacidad' ? 1 : 0 }}" @endif @if($type==='password') minlength="8" autocomplete="new-password" @endif>
@if($field==='contrasena')<small class="text-secondary">Mínimo 8 caracteres. {{ $row ? 'Deja vacío para conservar la contraseña.' : '' }}</small>@endif
@endif
@error($field)<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
@endforeach</div><div class="border-top mt-4 pt-4 d-flex gap-2"><button class="btn btn-primary">{{ $row ? 'Guardar cambios' : 'Crear registro' }}</button><a class="btn btn-light" href="{{ route('crud.index',$module) }}">Cancelar</a></div></form>
@endsection
