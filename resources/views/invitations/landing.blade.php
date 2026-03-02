<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Invitation to Join a Colocation</h1>
            <p class="mt-2 text-sm text-gray-600">
                You were invited to join <span class="font-medium text-gray-900">{{ $colocation->name }}</span>.
            </p>
            <p class="mt-1 text-sm text-gray-500">Owner: {{ $colocation->owner->name }}</p>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Invitation Token</p>
            <p class="mt-1 break-all font-mono text-sm text-gray-700">{{ $token }}</p>
        </div>

        <div class="grid gap-3">
            <a
                href="{{ route('login') }}"
                class="inline-flex w-full items-center justify-center rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500"
            >
                I already have an account
            </a>

            <a
                href="{{ route('register') }}"
                class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                I don't have an account
            </a>
        </div>
    </div>
</x-guest-layout>

