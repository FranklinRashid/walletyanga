<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Staff · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">Staff Management</p>
                    <p class="text-sm text-zinc-500">Create internal users for compliance and operations.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="wy-button wy-button-secondary">Admin</a>
                    <a href="{{ route('admin.staff.create') }}" class="wy-button wy-button-primary">Create Staff</a>
                </div>
            </nav>

            @if (session('status'))
                <div class="wy-alert wy-alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <section class="wy-table-card">
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
                        <tbody class="divide-y divide-zinc-200">
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
        @include("partials.mobile-nav")
    </body>
</html>
