@extends('layout')

@section('title')
    {{ $page_title }}
@endsection

@section('content')
<x-navigation.navbar />
<main>
    <x-navigation.sidebar />
</main>
@endsection