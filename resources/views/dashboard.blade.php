<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Reputation</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $myReputation }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Expenses Paid</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($myExpensesTotal, 2) }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active Colocation</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">
                {{ $activeColocation?->name ?? 'No active colocation' }}
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-600">
            Manage colocations, expenses, and settlements from the Colocations section.
        </p>
        <a href="{{ route('colocations.index') }}" class="mt-4 inline-flex rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
            Open Colocations
        </a>
    </div>
</x-app-layout>
