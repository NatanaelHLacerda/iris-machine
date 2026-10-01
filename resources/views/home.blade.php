@extends('layouts.app')

@section('content')
    @include('partials.nav')

    <main id="conteudo">
        @include('partials.hero')
        @include('partials.audience')
        @include('partials.modules')
        @include('partials.terminal')
        @include('partials.plans')
        @include('partials.lead')
        @include('partials.faq')
    </main>

    @include('partials.footer')
    @include('partials.cta-bar')
@endsection
