@extends('layouts.app')

@section('title', 'R4-01 Generator')

@section('content')
    @include('forms.partials.monthly-r4-01', ['embedded' => false])
@endsection
