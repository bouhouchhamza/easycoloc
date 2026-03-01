<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Join by Token</h1>
    </x-slot>

    <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Paste the invitation token received from a colocation owner.</p>

        <form method="POST" action="{{ route('colocations.token.submit') }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="token" class="mb-1 block text-sm font-medium text-slate-700">Invitation Token</label>
                <input
                    id="token"
                    type="text"
                    name="token"
                    value="{{ old('token') }}"
                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    required
                >
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('colocations.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Back
                </a>
                <button type="submit" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                    Continue
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
