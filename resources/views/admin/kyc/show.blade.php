<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC review · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">KYC review</p>
                    <p class="text-sm text-zinc-400">{{ $kyc->user?->name }} · {{ $kyc->user?->email }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.kyc.index') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Queue</a>
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Dashboard</a>
                </div>
            </nav>

            @if ($errors->any())
                <div class="rounded-md border border-red-900 bg-red-950/40 px-4 py-3 text-sm text-red-200">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</p>
                <p class="mt-1 text-lg font-semibold capitalize text-white">{{ str_replace('_', ' ', $kyc->status) }}</p>
                @if ($kyc->submitted_at)
                    <p class="mt-1 text-sm text-zinc-400">Submitted {{ $kyc->submitted_at->timezone(config('app.timezone'))->toDayDateTimeString() }}</p>
                @endif
                @if ($kyc->reviewed_at)
                    <p class="mt-2 text-sm text-zinc-400">
                        Reviewed {{ $kyc->reviewed_at->timezone(config('app.timezone'))->toDayDateTimeString() }}
                        @if ($kyc->reviewedBy)
                            by {{ $kyc->reviewedBy->name }}
                        @endif
                    </p>
                @endif
                @if ($kyc->review_notes)
                    <p class="mt-3 text-sm text-zinc-300"><span class="text-zinc-500">Notes:</span> {{ $kyc->review_notes }}</p>
                @endif
            </section>

            <section class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-5 text-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Date of birth</p>
                        <p class="mt-1 text-white">{{ optional($kyc->date_of_birth)->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Identity</p>
                        <p class="mt-1 text-white">{{ $identityTypes[$kyc->identity_type] ?? $kyc->identity_type ?? '—' }}</p>
                        <p class="mt-0.5 font-mono text-zinc-300">{{ $kyc->identity_number ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs uppercase text-zinc-500">Address</p>
                        <p class="mt-1 text-white">{{ $kyc->address ?? '—' }}</p>
                        <p class="mt-1 text-zinc-300">{{ $kyc->city_district ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Occupation</p>
                        <p class="mt-1 text-white">{{ $kyc->occupation ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Source of funds</p>
                        <p class="mt-1 text-white">{{ $sourcesOfFunds[$kyc->source_of_funds] ?? $kyc->source_of_funds ?? '—' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs uppercase text-zinc-500">ID document</p>
                    @if ($kyc->id_document_path)
                        <a href="{{ route('admin.kyc.document', $kyc) }}" class="mt-2 inline-flex rounded-md border border-emerald-700 bg-emerald-950/40 px-3 py-2 text-sm font-medium text-emerald-200 hover:bg-emerald-900/50">Download file</a>
                    @else
                        <p class="mt-2 text-zinc-500">No file on record.</p>
                    @endif
                </div>
            </section>

            @if ($canDecide && $kyc->status === 'pending')
                <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                    <h2 class="font-semibold text-white">Decision</h2>
                    <form method="POST" action="{{ route('admin.kyc.decide', $kyc) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="review_notes" class="text-sm text-zinc-400">Notes (required if rejecting)</label>
                            <textarea id="review_notes" name="review_notes" rows="3" class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">{{ old('review_notes') }}</textarea>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="decision" value="approve" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
                            <button type="submit" name="decision" value="reject" class="rounded-md border border-red-800 bg-red-950/50 px-4 py-2 text-sm font-semibold text-red-200 hover:bg-red-900/40">Reject</button>
                        </div>
                    </form>
                </section>
            @endif
        </main>
    </body>
</html>
