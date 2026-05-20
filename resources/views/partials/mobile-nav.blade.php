@php
    $isAuthenticated = auth()->check();
    $user = auth()->user();
    $isStaff = $user?->isStaff() ?? false;
    $navItems = $isAuthenticated
        ? [
            ['label' => 'Dashboard', 'href' => route('wallet.dashboard'), 'active' => request()->routeIs('wallet.dashboard')],
            ['label' => 'Add money', 'href' => route('wallet.add-money'), 'active' => request()->routeIs('wallet.add-money')],
            ['label' => 'Convert', 'href' => route('wallet.convert'), 'active' => request()->routeIs('wallet.convert')],
            ['label' => 'Cards', 'href' => route('wallet.cards'), 'active' => request()->routeIs('wallet.cards')],
            ['label' => 'KYC', 'href' => route('kyc.edit'), 'active' => request()->routeIs('kyc.edit')],
        ]
        : [
            ['label' => 'Login', 'href' => route('login'), 'active' => request()->routeIs('login')],
            ['label' => 'Register', 'href' => route('register'), 'active' => request()->routeIs('register')],
        ];

    if ($isStaff) {
        $navItems[] = ['label' => 'Admin', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.*')];
    }
@endphp

<div data-mobile-nav class="lg:hidden">
    <a href="{{ $isAuthenticated ? route('wallet.dashboard') : route('login') }}" class="fixed left-4 top-4 z-40 flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white/95 px-3 py-2 shadow-lg shadow-zinc-300/60 backdrop-blur" aria-label="Wallet Yanga home">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#4cd14f] text-xs font-black text-zinc-950">WY</span>
        <span class="text-sm font-semibold text-zinc-950">Wallet Yanga</span>
    </a>

    <button type="button" data-mobile-nav-open class="fixed right-4 top-4 z-40 flex h-11 w-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white/95 text-zinc-950 shadow-lg shadow-zinc-300/60 backdrop-blur" aria-label="Open navigation" aria-expanded="false">
        <span class="sr-only">Open navigation</span>
        <span class="flex flex-col gap-1.5">
            <span class="block h-0.5 w-5 rounded bg-zinc-950"></span>
            <span class="block h-0.5 w-5 rounded bg-zinc-950"></span>
            <span class="block h-0.5 w-5 rounded bg-zinc-950"></span>
        </span>
    </button>

    <div data-mobile-nav-overlay class="fixed inset-0 z-50 hidden bg-black/60 opacity-0 backdrop-blur-sm transition-opacity duration-200"></div>

    <aside data-mobile-nav-drawer class="fixed inset-y-0 left-0 z-50 flex w-[86vw] max-w-sm -translate-x-full flex-col border-r border-zinc-200 bg-zinc-50 shadow-2xl shadow-black/40 transition-transform duration-200">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-emerald-300">Wallet Yanga</p>
                <p class="mt-1 truncate text-xs text-zinc-500">{{ $isAuthenticated ? $user->email : 'Malawi fintech wallet' }}</p>
            </div>
            <button type="button" data-mobile-nav-close class="flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-300 text-zinc-700" aria-label="Close navigation">
                <span class="text-xl leading-none">&times;</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <nav class="space-y-1">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="flex items-center justify-between rounded-lg px-3 py-3 text-sm font-medium {{ $item['active'] ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-white' }}">
                        <span>{{ $item['label'] }}</span>
                        @if ($item['active'])
                            <span class="h-2 w-2 rounded-full bg-zinc-50"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="border-t border-zinc-200 p-4">
            @if ($isAuthenticated)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-zinc-300 px-4 py-3 text-left text-sm font-semibold text-zinc-950 hover:border-zinc-400">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('register') }}" class="block w-full rounded-lg bg-emerald-400 px-4 py-3 text-center text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                    Create account
                </a>
            @endif
        </div>
    </aside>
</div>
