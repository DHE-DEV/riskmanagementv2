@extends('layouts.iframe-fragment')

@section('title', 'Passolution Travel Information Platform – Navigation')

@section('content')
    <x-public-navigation :active="request()->query('active', 'dashboard')" />
@endsection
