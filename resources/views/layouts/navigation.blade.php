<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight text-slate-900">
                EasyColoc
            </a>

            <div class="hidden items-center gap-4 md:flex">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-cyan-700' : 'text-slate-600 hover:text-slate-900' }}">
                    Dashboard
                </a>
                <a href="{{ route('colocations.index') }}" class="text-sm font-medium {{ request()->routeIs('colocations.*') ? 'text-cyan-700' : 'text-slate-600 hover:text-slate-900' }}">
                    Colocations
                </a>
                @if (auth()->user()->isGlobalAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium {{ request()->routeIs('admin.*') ? 'text-cyan-700' : 'text-slate-600 hover:text-slate-900' }}">
                        Admin
                    </a>
                @endif
            </div>
        </div>

        <div class="hidden items-center gap-4 md:flex">
            <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                {{ auth()->user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Logout
                </button>
            </form>
        </div>

        <button
            @click="open = !open"
            type="button"
            class="inline-flex items-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 md:hidden"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div x-show="open" class="border-t border-slate-200 px-4 py-3 md:hidden">
        <div class="flex flex-col gap-2">
            <a href="{{ route('dashboard') }}" class="rounded px-2 py-1 text-sm {{ request()->routeIs('dashboard') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-700' }}">
                Dashboard
            </a>
            <a href="{{ route('colocations.index') }}" class="rounded px-2 py-1 text-sm {{ request()->routeIs('colocations.*') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-700' }}">
                Colocations
            </a>
            @if (auth()->user()->isGlobalAdmin())
                <a href="{{ route('admin.dashboard') }}" class="rounded px-2 py-1 text-sm {{ request()->routeIs('admin.*') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-700' }}">
                    Admin
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="rounded px-2 py-1 text-sm text-slate-700">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded bg-slate-900 px-3 py-2 text-left text-sm font-medium text-white">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>
