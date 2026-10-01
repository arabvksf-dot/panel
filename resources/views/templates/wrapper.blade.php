<!DOCTYPE html>
<html>
    <head>
        <title>{{ config('site.name') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="{{ config('site.seo.robots') }}">
            <meta name="description" content="{{ config('site.seo.description') }}">
            <link rel="apple-touch-icon" href="{{ config('site.favicon') }}">
            <link rel="icon" type="image/png" href="{{ config('site.favicon') }}">
            <meta name="theme-color" content="{{ config('site.theme_color') }}">
            <link rel="stylesheet" href="/assets/theme-tokens.css">
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.PterodactylUser = @js(Auth::user()->toVueObject());
                </script>
            @endif
            @if(!empty($authConfirmationToken))
                <script>
                    window.AuthConfirmationToken = @js($authConfirmationToken);
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = @js($siteConfiguration);
                </script>
            @endif
        @show

        @yield('assets')

        @include('layouts.scripts')
    </head>
    <body class="{{ $css['body'] ?? 'bg-neutral-50' }}">
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
        @section('scripts')
            {!! $asset->js('main.js') !!}
        @show
    </body>
</html>
