@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 style="font-size:1.4rem; margin:0 0 0.3rem;">Overview</h1>
    <p style="color: var(--text-muted); margin:0 0 1.5rem; font-size:0.9rem;">
        A quick look at what's happening across LSPDMS.
    </p>

    <div style="background: var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem;">
        <p style="margin:0; color: var(--text-muted); font-size:0.9rem;">
            Dashboard widgets go here.
        </p>
    </div>

@endsection