@extends('layouts.admin')

@section('title', 'Nuevo usuario')
@section('subtitle', 'Crea una cuenta y asígnale un rol')

@section('content')
    <div class="max-w-3xl">
        @include('admin.users.partials.form', [
            'user' => $user,
            'roles' => $roles,
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'submitLabel' => 'Crear usuario',
        ])
    </div>
@endsection
