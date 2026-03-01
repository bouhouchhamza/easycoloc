<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'EasyColoc') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900">
        <main class="mx-auto flex min-h-screen max-w-4xl items-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:p-10">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">EasyColoc</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
                    Colocation Management Demo
                </h1>
                <p class="mt-4 max-w-2xl text-sm text-slate-600 md:text-base">
                    Manage colocations, expenses, settlements, payments, reputation, and admin moderation in a monolithic Laravel MVC app.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </main>
    </body>
</html>
