<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-900">{{ $colocation->name }}</h1>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                {{ $colocation->status }}
            </span>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Owner</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $colocation->owner->name }}</p>

                @if ((int) auth()->id() === (int) $colocation->owner_id)
                    <div class="mt-4 rounded-lg bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Invitation Token</p>
                        <p class="mt-1 break-all font-mono text-sm text-slate-800">{{ $colocation->invite_token }}</p>
                        <p class="mt-2 text-xs text-slate-500">Invite link: {{ route('colocations.invite', $colocation->invite_token) }}</p>
                    </div>

                    <form method="POST" action="{{ route('colocations.invite.email', $colocation) }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <input
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            placeholder="Send invitation by email"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            required
                        >
                        <button type="submit" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                            Send
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Active Members</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($members as $member)
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <p class="font-medium text-slate-900">{{ $member->name }}</p>
                                <p class="text-sm text-slate-500">
                                    Role: {{ $member->pivot->role }} | Reputation: {{ $member->reputation }}
                                </p>
                            </div>

                            @if ((int) auth()->id() === (int) $colocation->owner_id && (int) $member->id !== (int) $colocation->owner_id)
                                <form method="POST" action="{{ route('colocations.remove-member', [$colocation, $member]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                        Remove
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <form method="GET" action="{{ route('colocations.show', $colocation) }}" class="flex items-end gap-3">
                        <div>
                            <label for="month" class="mb-1 block text-sm font-medium text-slate-700">Filter by Month</label>
                            <input id="month" name="month" type="month" value="{{ $month }}" class="rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                        </div>
                        <button type="submit" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                            Apply
                        </button>
                        <a href="{{ route('colocations.show', $colocation) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </form>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Expenses</h2>
                </div>

                @if ($expenses->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">No expenses found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Title</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Payer</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $expense->title ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $expense->category?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $expense->payer->name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $expense->expense_date->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($expense->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if ((int) auth()->id() === (int) $expense->user_id || (int) auth()->id() === (int) $colocation->owner_id)
                                                <form method="POST" action="{{ route('colocations.expenses.destroy', [$colocation, $expense]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @if ((int) auth()->id() === (int) $colocation->owner_id)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Categories</h2>
                    <form method="POST" action="{{ route('colocations.categories.store', $colocation) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input name="name" type="text" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="New category" required>
                        <button type="submit" class="rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                            Add
                        </button>
                    </form>

                    <ul class="mt-3 space-y-2">
                        @foreach ($categories as $category)
                            <li class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2 text-sm">
                                <span>{{ $category->name }}</span>
                                <form method="POST" action="{{ route('colocations.categories.destroy', [$colocation, $category]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:text-rose-900">Delete</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Add Expense</h2>
                <form method="POST" action="{{ route('colocations.expenses.store', $colocation) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
                        <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
                    </div>
                    <div>
                        <label for="expense_date" class="mb-1 block text-sm font-medium text-slate-700">Expense Date</label>
                        <input id="expense_date" name="expense_date" type="date" value="{{ old('expense_date', now()->toDateString()) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
                    </div>
                    <div>
                        <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <select id="category_id" name="category_id" class="w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="">No category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" class="mb-1 block text-sm font-medium text-slate-700">Payer</label>
                        <select id="user_id" name="user_id" class="w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected(old('user_id', auth()->id()) == $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                        Add Expense
                    </button>
                </form>
            </div>

            @if ((int) auth()->id() === (int) $colocation->owner_id)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <h2 class="text-base font-semibold text-amber-800">Owner Actions</h2>
                    <p class="mt-1 text-sm text-amber-700">Cancelling sets colocation status to cancelled for everyone.</p>
                    <form method="POST" action="{{ route('colocations.cancel', $colocation) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                            Cancel Colocation
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Leave Colocation</h2>
                    <p class="mt-1 text-sm text-slate-500">Leaving updates your reputation based on your debt.</p>
                    <form method="POST" action="{{ route('colocations.leave', $colocation) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                            Leave
                        </button>
                    </form>
                </div>
            @endif

            <a href="{{ route('colocations.settlement.show', $colocation) }}" class="inline-flex w-full justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Open Settlement
            </a>
        </div>
    </div>
</x-app-layout>
