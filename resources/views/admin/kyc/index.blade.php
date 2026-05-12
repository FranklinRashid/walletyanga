<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC queue · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">Wallet Yanga Admin</p>
                    <p class="text-sm text-zinc-400">KYC queue · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Dashboard</a>
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.staff.index') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Staff</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Logout</button>
                    </form>
                </div>
            </nav>

            @if (session('status'))
                <p class="rounded-md border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</p>
            @endif

            <section>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Pending KYC reviews</h1>
                <p class="mt-2 text-sm text-zinc-400">Customers who submitted details and ID documents for compliance.</p>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            @forelse ($pendingProfiles as $profile)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-white">{{ $profile->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-300">{{ $profile->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-400">{{ optional($profile->submitted_at)->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.kyc.show', $profile) }}" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-zinc-500">No submissions awaiting review.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </body>
</html>
