<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Invitation</h1>
    </x-slot>

    <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @if (! $colocation)
            <p class="text-sm text-rose-600">This invitation is invalid or expired.</p>
            <a href="{{ route('colocations.index') }}" class="mt-4 inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back
            </a>
        @else
            <p class="text-sm text-slate-500">You were invited to join:</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $colocation->name }}</p>
            <p class="mt-1 text-sm text-slate-500">Owner: {{ $colocation->owner->name }}</p>

            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Token</p>
                <p class="mt-1 break-all font-mono text-sm text-slate-700">{{ $token }}</p>
            </div>

            @if ($hasActiveColocation)
                <p class="mt-4 text-sm text-rose-600">You already belong to an active colocation and cannot join another one.</p>
                <a href="{{ route('colocations.index') }}" class="mt-4 inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Back
                </a>
            @else
                <form method="POST" action="{{ route('colocations.invite.respond') }}" class="mt-5 space-y-3">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <button type="submit" name="action" value="accept" class="w-full rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                        Accept Invitation
                    </button>

                    <button type="submit" name="action" value="refuse" class="w-full rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Refuse Invitation
                    </button>
                </form>
            @endif
        @endif
    </div>
</x-app-layout>
