<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC review · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">KYC review</p>
                    <p class="text-sm text-zinc-500">{{ $kyc->user?->name }} · {{ $kyc->user?->email }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.kyc.index') }}" class="wy-button wy-button-secondary">Queue</a>
                    <a href="{{ route('admin.dashboard') }}" class="wy-button wy-button-secondary">Dashboard</a>
                </div>
            </nav>

            @if ($errors->any())
                <div class="wy-alert wy-alert-danger">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="wy-card">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</p>
                <p class="mt-1 text-lg font-semibold capitalize text-black">{{ str_replace('_', ' ', $kyc->status) }}</p>
                @if ($kyc->submitted_at)
                    <p class="mt-1 text-sm text-zinc-500">Submitted {{ $kyc->submitted_at->timezone(config('app.timezone'))->toDayDateTimeString() }}</p>
                @endif
                @if ($kyc->reviewed_at)
                    <p class="mt-2 text-sm text-zinc-500">
                        Reviewed {{ $kyc->reviewed_at->timezone(config('app.timezone'))->toDayDateTimeString() }}
                        @if ($kyc->reviewedBy)
                            by {{ $kyc->reviewedBy->name }}
                        @endif
                    </p>
                @endif
                @if ($kyc->review_notes)
                    <p class="mt-3 text-sm text-zinc-600"><span class="text-zinc-500">Notes:</span> {{ $kyc->review_notes }}</p>
                @endif
            </section>

            <section class="wy-card space-y-4 text-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Date of birth</p>
                        <p class="mt-1 text-black">{{ optional($kyc->date_of_birth)->format('Y-m-d') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Identity</p>
                        <p class="mt-1 text-black">{{ $identityTypes[$kyc->identity_type] ?? $kyc->identity_type ?? '—' }}</p>
                        <p class="mt-0.5 font-mono text-zinc-600">{{ $kyc->identity_number ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs uppercase text-zinc-500">Address</p>
                        <p class="mt-1 text-black">{{ $kyc->address ?? '—' }}</p>
                        <p class="mt-1 text-zinc-600">{{ $kyc->city_district ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Occupation</p>
                        <p class="mt-1 text-black">{{ $kyc->occupation ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-zinc-500">Source of funds</p>
                        <p class="mt-1 text-black">{{ $sourcesOfFunds[$kyc->source_of_funds] ?? $kyc->source_of_funds ?? '—' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs uppercase text-zinc-500">ID document</p>
                    @if ($kyc->id_document_path)
                        <a href="{{ route('admin.kyc.document', $kyc) }}" class="wy-button wy-button-secondary mt-2">Download file</a>
                    @else
                        <p class="mt-2 text-zinc-500">No file on record.</p>
                    @endif
                </div>
            </section>

            @if ($canDecide && $kyc->status === 'pending')
                <section class="wy-panel">
                    <h2 class="font-semibold text-black">Decision</h2>
                    <form method="POST" action="{{ route('admin.kyc.decide', $kyc) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="review_notes" class="text-sm text-zinc-500">Notes (required if rejecting)</label>
                            <textarea id="review_notes" name="review_notes" rows="3" class="wy-field text-sm">{{ old('review_notes') }}</textarea>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="decision" value="approve" class="wy-button wy-button-primary">Approve</button>
                            <button type="submit" name="decision" value="reject" class="wy-button border border-red-200 bg-red-50 text-red-700 hover:border-red-300">Reject</button>
                        </div>
                    </form>
                </section>
            @endif
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
