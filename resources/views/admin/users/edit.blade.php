@extends('layouts.admin')

@section('title', 'Editar usuario')
@section('subtitle', 'Actualiza la cuenta, su rol y su acceso')

@section('content')
    <div class="max-w-3xl">
        @include('admin.users.partials.form', [
            'user' => $user,
            'roles' => $roles,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'submitLabel' => 'Guardar cambios',
        ])
    </div>
@endsection
