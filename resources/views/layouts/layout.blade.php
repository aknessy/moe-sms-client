<!Doctype html>
<html data-theme="nord">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title> @yield('title', 'Staff Management') </title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css2?family=figtree:400;500;600;700&display=swap" rel="stylesheet">
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="w-screen h-screen bg-base-200 text-base-content">
            @yield('content')
        </div>
    </body>
</html>