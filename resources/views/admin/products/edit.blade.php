@extends('layouts.admin')

@section('title', 'Editar producto')
@section('subtitle', 'Actualiza la información de la pieza')

@section('content')
    <div class="max-w-5xl">
        @include('admin.products.partials.form', [
            'product' => $product,
            'categories' => $categories,
            'action' => route('admin.products.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Guardar cambios',
        ])
    </div>
@endsection
