@extends('layouts.app')

@section('title', 'Form Generator')

@section('content')

<style>
    .fg-wrapper {
        max-width: 900px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .fg-title {
        text-align: center;
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 40px;
    }

    .fg-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    @media (min-width: 768px) {
        .fg-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .fg-card {
        display: block;
        text-decoration: none;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 48px 24px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }

    .fg-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .fg-icon-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px auto;
        transition: background-color 0.2s ease;
    }

    .fg-icon-circle svg {
        width: 28px;
        height: 28px;
        display: block;
    }

    .fg-card.weekly .fg-icon-circle {
        background: #dbeafe;
    }
    .fg-card.weekly:hover {
        border-color: #60a5fa;
    }
    .fg-card.weekly:hover .fg-icon-circle {
        background: #3b82f6;
    }
    .fg-card.weekly .fg-icon-circle svg {
        stroke: #2563eb;
        transition: stroke 0.2s ease;
    }
    .fg-card.weekly:hover .fg-icon-circle svg {
        stroke: #ffffff;
    }

    .fg-card.monthly .fg-icon-circle {
        background: #dcfce7;
    }
    .fg-card.monthly:hover {
        border-color: #4ade80;
    }
    .fg-card.monthly:hover .fg-icon-circle {
        background: #22c55e;
    }
    .fg-card.monthly .fg-icon-circle svg {
        stroke: #16a34a;
        transition: stroke 0.2s ease;
    }
    .fg-card.monthly:hover .fg-icon-circle svg {
        stroke: #ffffff;
    }

    .fg-card h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }

    .fg-card p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
    }
</style>

<div class="fg-wrapper">

    <h1 class="fg-title">Form Generator</h1>

    <div class="fg-grid">

        {{-- Weekly Card --}}
        <a href="{{ route('forms.weekly') }}" class="fg-card weekly">
            <div class="fg-icon-circle">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h2>Weekly</h2>
            <p>Generate and regenerate weekly forms</p>
        </a>

        {{-- Monthly Card --}}
        <a href="{{ route('forms.monthly') }}" class="fg-card monthly">
            <div class="fg-icon-circle">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h2>Monthly</h2>
            <p>Generate and regenerate monthly forms</p>
        </a>

    </div>

</div>

@endsection