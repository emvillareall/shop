@extends('layouts.app')

@section('template_title')
    Colores
@endsection

@section('content')
    <div class="container-fluid py-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <h1 class="text-lg font-semibold text-slate-800">Gestion de colores</h1>
                <a href="{{ route('colores.create') }}" class="btn btn-primary">Nuevo color</a>
            </div>

            <div class="px-4 py-3">
                <form method="GET" action="{{ route('colores.index') }}" class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_auto_auto]">
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Buscar por nombre o codigo (#RRGGBB)">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="{{ route('colores.index') }}" class="btn btn-secondary">Limpiar</a>
                </form>
            </div>

            @if(session('success'))
                <div class="mx-4 mb-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive px-4 pb-4">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Muestra</th>
                        <th>Nombre</th>
                        <th>Codigo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($colores as $color)
                        <tr>
                            <td>{{ ++$i }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="inline-block h-8 w-8 rounded border border-slate-300 shadow-sm"
                                          style="background-color: {{ $color->codigo_color }};"
                                          title="{{ $color->nombre_color }}"></span>
                                    <small class="text-muted">{{ $color->codigo_color }}</small>
                                </div>
                            </td>
                            <td>{{ $color->nombre_color }}</td>
                            <td><code>{{ $color->codigo_color }}</code></td>
                            <td class="text-end">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('colores.edit', $color->id) }}" class="btn btn-sm btn-success">Editar</a>
                                    <form action="{{ route('colores.destroy', $color->id) }}" method="POST" onsubmit="return confirm('Deseas eliminar este color?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">No hay colores registrados.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {!! $colores->links() !!}
        </div>
    </div>
@endsection
