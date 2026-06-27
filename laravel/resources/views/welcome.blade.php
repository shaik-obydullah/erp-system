<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ERP System</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-blue-600 mb-4">Hello World</h1>
            <p class="text-xl text-gray-600">Welcome to ERP System</p>
            <p class="mt-8 text-sm text-gray-400">Laravel {{ app()->version() }}</p>
        </div>
    </body>
</html>
