<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell grid items-start gap-8 lg:grid-cols-[0.85fr_1.15fr]">
            <section class="pt-4">
                <a href="{{ route('wallet.dashboard') }}" class="wy-button wy-button-secondary">Back to dashboard</a>
                <p class="wy-kicker mt-8">Customer verification</p>
                <h1 class="wy-hero-title mt-3">Submit your KYC</h1>
                <p class="wy-subtitle max-w-xl text-base">
                    Wallet Yanga needs your identity and source-of-funds details before deposits, USD conversion, and card payments can be enabled.
                </p>

                <div class="wy-card mt-6">
                    <p class="text-sm text-zinc-500">Current status</p>
                    <p class="mt-2 text-xl font-semibold capitalize">{{ str_replace('_', ' ', $kyc->status) }}</p>
                </div>
            </section>

            <section class="wy-panel">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold">Identity Details</h2>
                    <p class="mt-1 text-sm text-zinc-500">Use details that match your official document.</p>
                </div>

                <form method="POST" action="{{ route('kyc.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="date_of_birth" class="wy-label">Date of birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($kyc->date_of_birth)->format('Y-m-d')) }}" required class="wy-field text-sm">
                            @error('date_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="identity_type" class="wy-label">Document type</label>
                            <select id="identity_type" name="identity_type" required class="wy-field text-sm">
                                @foreach ($identityTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('identity_type', $kyc->identity_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('identity_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="identity_number" class="wy-label">National ID/passport number</label>
                        <input id="identity_number" name="identity_number" value="{{ old('identity_number', $kyc->identity_number) }}" required class="wy-field text-sm">
                        @error('identity_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="address" class="wy-label">Residential address</label>
                        <input id="address" name="address" value="{{ old('address', $kyc->address) }}" required class="wy-field text-sm">
                        @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="city_district" class="wy-label">City/district</label>
                            <input id="city_district" name="city_district" value="{{ old('city_district', $kyc->city_district) }}" required class="wy-field text-sm">
                            @error('city_district')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="occupation" class="wy-label">Occupation</label>
                            <input id="occupation" name="occupation" value="{{ old('occupation', $kyc->occupation) }}" required class="wy-field text-sm">
                            @error('occupation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="source_of_funds" class="wy-label">Source of funds</label>
                        <select id="source_of_funds" name="source_of_funds" required class="wy-field text-sm">
                            @foreach ($sourcesOfFunds as $value => $label)
                                <option value="{{ $value }}" @selected(old('source_of_funds', $kyc->source_of_funds) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('source_of_funds')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="id_document" class="wy-label">ID document</label>
                        <input id="id_document" type="file" name="id_document" accept=".jpg,.jpeg,.png,.pdf" class="wy-field border-dashed text-sm text-zinc-600 file:mr-4 file:rounded-full file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-950 hover:border-zinc-400">
                        <p class="mt-1 text-xs text-zinc-500">Upload a JPG, PNG, or PDF up to 5MB. {{ $kyc->id_document_path ? 'A document is already on file.' : '' }}</p>
                        @error('id_document')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="wy-button wy-button-primary w-full">
                        Submit KYC
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
