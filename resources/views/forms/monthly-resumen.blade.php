@extends('layouts.app')

@section('title', 'Resumen Generator')

@section('content')
    @include('forms.partials.monthly-resumen', ['embedded' => false])
@endsection
