@extends('layouts.app')

@section('title', 'Monthly Generators')

@section('content')
    <style>
        .monthly-generators-page { max-width:1500px; margin:0 auto; }
        .monthly-generators-back { display:inline-flex; margin-bottom:1rem; color:var(--accent); font-weight:700; text-decoration:none; }
        .monthly-generators-section + .monthly-generators-section { margin-top:2rem; padding-top:1.5rem; border-top:2px solid var(--border); }
        .monthly-generators-section > h2 { margin:0 0 1rem; font-size:1.2rem; }
    </style>
    <main class="monthly-generators-page">
        <a class="monthly-generators-back" href="{{ route('form-generator', ['panel' => 'monthly', 'monthly_section' => 'monthly-forms']) }}">← Monthly Forms</a>
        <section class="monthly-generators-section" aria-labelledby="resumen-generator-heading">
            <h2 id="resumen-generator-heading">Resumen Generator</h2>
            @include('forms.partials.monthly-resumen', ['embedded' => true])
        </section>
        <section class="monthly-generators-section" aria-labelledby="r401-generator-heading">
            <h2 id="r401-generator-heading">R4-01 Generator</h2>
            @include('forms.partials.monthly-r4-01', ['embedded' => true])
        </section>
    </main>
@endsection
