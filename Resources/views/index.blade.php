@extends('n8nchat::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('n8nchat.name') !!}
    </p>
@stop
