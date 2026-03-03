<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Admin Dashboard</h1>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Users</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['users'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Colocations</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['colocations'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Expenses</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['expenses'] }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Users</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Roles</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->getRoleNames()->join(', ') ?: 'user' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $user->is_banned ? 'Banned' : 'Active' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! $user->hasRole('global_admin'))
                                    <form method="POST" action="{{ route('admin.users.ban-status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_banned" value="{{ $user->is_banned ? 0 : 1 }}">
                                        <button type="submit" class="rounded-md border px-3 py-2 text-xs font-semibold {{ $user->is_banned ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' : 'border-rose-300 text-rose-700 hover:bg-rose-50' }}">
                                            {{ $user->is_banned ? 'Unban' : 'Ban' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">Protected</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
