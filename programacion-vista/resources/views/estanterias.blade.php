@extends('layouts.nav')

@section('title', 'Estanterías')

@section('ladoizq')

{{-- ERRORES DE VALIDACIÓN --}}
@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="w-100">
            @foreach ($errors->all() as $error)
            <p class="mb-0 fw-bold">{{ $error }}</p>
            @endforeach
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

{{-- MENSAJES EXITOSOS --}}
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <div class="fw-bold">{{ session('success') }}</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="row h-100 g-3">

    {{-- FORMULARIO DE ALTA DE ESTANTERÍA --}}
    <div class="col-lg-4">
        <div class="card bg-dark text-white shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-layer-group me-2"></i>Nueva Estantería
                </h5>
                <a href="{{ route('views.lista') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i>Productos
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('estanterias.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="identificador" class="form-label">
                            Letra / Número de estantería
                        </label>
                        <input type="text"
                            class="form-control"
                            id="identificador"
                            name="identificador"
                            value="{{ old('identificador') }}"
                            maxlength="50"
                            placeholder="Ej: A, 1, E-02"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="pasillo" class="form-label">
                            Pasillo
                        </label>
                        <input type="text"
                            class="form-control"
                            id="pasillo"
                            name="pasillo"
                            value="{{ old('pasillo') }}"
                            maxlength="50"
                            placeholder="Ej: Pasillo 1"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="posiciones" class="form-label">
                            Posiciones (cantidad máxima: 1 a N)
                        </label>
                        <input type="number"
                            class="form-control"
                            id="posiciones"
                            name="posiciones"
                            value="{{ old('posiciones') }}"
                            min="1"
                            max="500"
                            placeholder="Ej: 10"
                            required>
                        <div class="form-text text-light opacity-75">
                            Al cargar un producto se podrá elegir una posición entre 1 y este número.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-plus me-1"></i>Guardar Estantería
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- LISTADO DE ESTANTERÍAS --}}
    <div class="col-lg-8">
        <div class="card bg-dark text-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    Estanterías Registradas
                </h5>

                @if($estanterias->isEmpty())
                <div class="alert alert-info mb-0" role="alert">
                    Aún no hay estanterías dadas de alta.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-dark table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Estantería</th>
                                <th>Pasillo</th>
                                <th>Posiciones</th>
                                <th>Formato en producto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estanterias as $estanteria)
                            <tr>
                                <td>{{ $estanteria->id_estanteria }}</td>
                                <td>{{ $estanteria->identificador }}</td>
                                <td>{{ $estanteria->pasillo }}</td>
                                <td>1 a {{ $estanteria->posiciones }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $estanteria->identificador }} - {{ $estanteria->pasillo }} - 1
                                    </span>
                                </td>
                                <td>{{ $estanteria->estado }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection