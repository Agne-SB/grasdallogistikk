@extends('admin.layout')

@section('content')
    <h1>Admin oversikt</h1>
    <p>Velkommen, {{ auth()->user()->name }}.</p>
    <p>Velg i menyen øverst:</p>
    <ul>
        <li><a class="auth-link" href="{{ url('/admin/users') }}">Brukere</a></li>
        <li><a class="auth-link" href="{{ url('/admin/closed-projects') }}">Lukkede prosjekter</a></li>
    </ul>
@endsection

