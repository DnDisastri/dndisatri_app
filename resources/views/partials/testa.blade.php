<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

{{-- Colore del browser sincronizzato con il tema di sistema. --}}
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#f5f5f5">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#111111">

<title>@yield('title', $title ?? config('app.name'))</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- I font sono separati da @vite e vanno caricati prima per i preload. --}}
{{ Vite::fonts() }}
@vite(['resources/css/app.css', 'resources/js/app.js'])

@include('partials.tema')
