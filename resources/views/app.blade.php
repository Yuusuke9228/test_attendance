<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'TimePals') }}</title>

    <link rel="shortcut icon" href="{{ asset('assets/favicon.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- JP fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&family=Noto+Serif+JP:wght@200;300;400;500;600;700;900&family=Yuji+Syuku&family=Zen+Kaku+Gothic+New:wght@300;400;500;700;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/scroll-hint@latest/css/scroll-hint.css">
    <style>
        .wf-sans {
            font-family: 'Noto Sans JP', sans-serif;
        }

        .wf-serif {
            font-family: 'Noto Serif JP', serif;
        }

        .wf-yuji {
            font-family: 'Yuji Syuku', serif;
        }

        .wf-kaku {
            font-family: 'Zen Kaku Gothic New', sans-serif;
        }
    </style>
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="antialiased serif_jp">
    @inertia
</body>

</html>
