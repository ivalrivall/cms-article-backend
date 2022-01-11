@extends('beautymail::templates.ark')

@section('content')

    @include('beautymail::templates.ark.contentStart')
        <h3>Test!</h3>
        <h4 class="secondary"><strong>Halo {{ $user->name }}</strong></h4>
        <p>Ini adalah testing pengiriman email</p>

    @include('beautymail::templates.ark.contentEnd')

@stop
