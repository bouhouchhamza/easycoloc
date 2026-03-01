<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Settlement - {{ $colocation->name }}</h1>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Expenses</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['total_expenses'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Share Per Person</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['share_per_member'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Active Members</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['members']->count() }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Balances</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($summary['members'] as $member)
                    @php
                        $paid = $summary['totals_paid'][$member->id] ?? 0;
                        $balance = $summary['balances'][$member->id] ?? 0;
                    @endphp
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <p class="font-medium text-slate-900">{{ $member->name }}</p>
                            <p class="text-sm text-slate-500">Paid: {{ number_format($paid, 2) }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $balance >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $balance >= 0 ? '+' : '' }}{{ number_format($balance, 2) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Who Owes Who</h2>
            </div>

            @if (empty($summary['transfers']))
                <p class="px-5 py-6 text-sm text-slate-500">No transactions needed.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">From</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">To</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($summary['transfers'] as $transfer)
                                <tr>
                                    <td class="px-4 py-3 text-slate-700">{{ $transfer['from_name'] }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $transfer['to_name'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($transfer['amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ((int) auth()->id() === (int) $transfer['from_user_id'])
                                            <form method="POST" action="{{ route('colocations.payments.store', $colocation) }}">
                                                @csrf
                                                <input type="hidden" name="from_user_id" value="{{ $transfer['from_user_id'] }}">
                                                <input type="hidden" name="to_user_id" value="{{ $transfer['to_user_id'] }}">
                                                <input type="hidden" name="amount" value="{{ $transfer['amount'] }}">
                                                <button type="submit" class="rounded-md bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-500">
                                                    Mark Paid
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
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
</x-app-layout>
