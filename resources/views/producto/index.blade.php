@extends('layouts.app')

@section('template_title')
    Productos
@endsection

@section('content')
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <h1 class="text-base font-semibold">Listado de Productos</h1>
            </div>
            <div class="card-body">
                <livewire:productos-table />
            </div>
        </div>
    </div>
@endsection
