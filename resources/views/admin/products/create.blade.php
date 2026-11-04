@extends('layouts.admin')

@section('title', 'Nuevo producto')
@section('subtitle', 'Agrega una pieza al catálogo')

@section('content')
    <div class="max-w-5xl">
        @include('admin.products.partials.form', [
            'product' => $product,
            'categories' => $categories,
            'action' => route('admin.products.store'),
            'method' => 'POST',
            'submitLabel' => 'Crear producto',
        ])
    </div>
@endsection
