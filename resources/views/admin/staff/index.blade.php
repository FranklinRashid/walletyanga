<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Staff · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">Staff Management</p>
                    <p class="text-sm text-zinc-400">Create internal users for compliance and operations.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Admin</a>
                    <a href="{{ route('admin.staff.create') }}" class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">Create Staff</a>
                </div>
            </nav>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-lg border border-zinc-800 bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            @forelse ($staffUsers as $staff)
                                <tr>
                                    <td class="px-4 py-3">{{ $staff->name }}</td>
                                    <td class="px-4 py-3">{{ $staff->email }}</td>
                                    <td class="px-4 py-3">{{ $roles[$staff->role] ?? $staff->role }}</td>
                                    <td class="px-4 py-3">{{ $staff->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-zinc-500">No staff users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </body>
</html>
