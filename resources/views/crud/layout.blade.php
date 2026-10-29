
@extends('layouts.app')

@section('title', 'Sistema de Gestión de Bautizos')

@push('styles')
<style>
    .hero {
        background: linear-gradient(
            120deg,
            #f0ebf8 0%,
            #f9eff3 100%
        );
        border: 1px solid #ebe3f1;
        border-radius: 20px;
        padding: 35px;
        margin-bottom: 28px;
    }

    .hero h1 {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 600;
        color: #34263f;
    }

    .hero p {
        color: #777589;
        line-height: 1.7;
    }

    .eyebrow {
        color: #795e89;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.6px;
        text-transform: uppercase;
    }

    .brand {
        font-family: 'Playfair Display', Georgia, serif;
    }

    .module-link {
        display: block;
        color: inherit;
        text-decoration: none;
        height: 100%;
    }

    .module-link:hover {
        color: inherit;
    }

    .module-link .card {
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .module-link:hover .card {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(52, 38, 63, .10);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 35px;
    }

    .dashboard-stat {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 16px;
        padding: 25px;
    }

    .dashboard-stat-label {
        color: #777589;
        font-size: 12px;
        font-weight: 600;
    }

    .dashboard-stat-number {
        display: block;
        margin-top: 12px;
        font-size: 36px;
        font-weight: 800;
        color: #34263f;
    }

    .dashboard-modules {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .dashboard-module {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 16px;
        padding: 25px;
        transition: .2s ease;
    }

    .dashboard-module:hover {
        transform: translateY(-3px);
        border-color: #bda8c7;
        box-shadow: 0 10px 25px rgba(52,38,63,.08);
    }

    .dashboard-module h3 {
        font-size: 16px;
        margin: 12px 0;
    }

    .dashboard-module p {
        color: #777589;
        font-size: 12px;
    }

    .dashboard-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 22px;
    }

    .dashboard-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        transition: .2s ease;
    }

    .dashboard-button-primary {
        background: #694b76;
        color: white;
    }

    .dashboard-button-primary:hover {
        background: #34263f;
        color: white;
    }

    .dashboard-button-secondary {
        background: white;
        border: 1px solid #ded5e5;
        color: #51435b;
    }

    .dashboard-button-secondary:hover {
        background: #f2eaf4;
        color: #34263f;
    }

    .dashboard-section-title {
        font-size: 20px;
        font-weight: 800;
        margin: 30px 0 20px;
    }

    @media (max-width: 1100px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-modules {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 650px) {
        .dashboard-grid,
        .dashboard-modules {
            grid-template-columns: 1fr;
        }

        .hero {
            padding: 24px;
        }
    }
</style>
@endpush

@section('content')
    @yield('crud_content')
@endsection
