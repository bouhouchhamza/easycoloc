<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">My Colocations</h1>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Active Colocations</h2>
                </div>

                @if ($colocations->isEmpty())
                    <div class="px-5 py-8 text-sm text-slate-500">You are not in any active colocation yet.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($colocations as $colocation)
                            <div class="flex items-center justify-between px-5 py-4">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $colocation->name }}</p>
                                    <p class="text-sm text-slate-500">Owner: {{ $colocation->owner->name }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('colocations.show', $colocation) }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Open
                                    </a>
                                    <a href="{{ route('colocations.settlement.show', $colocation) }}" class="rounded-md bg-cyan-600 px-3 py-2 text-sm font-medium text-white hover:bg-cyan-500">
                                        Settlement
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Create Colocation</h2>
                <p class="mt-1 text-sm text-slate-500">Start a new shared house and become owner.</p>
                <a href="{{ route('colocations.create') }}" class="mt-4 inline-flex rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    New Colocation
                </a>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Join with Token</h2>
                <p class="mt-1 text-sm text-slate-500">Open the join page and submit your invitation token.</p>
                <a href="{{ route('colocations.join') }}" class="mt-4 inline-flex rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                    Join by Token
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
