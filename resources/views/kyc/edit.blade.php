<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto grid min-h-screen w-full max-w-6xl items-start gap-8 px-6 py-8 lg:grid-cols-[0.85fr_1.15fr]">
            <section class="pt-4">
                <a href="{{ route('wallet.dashboard') }}" class="text-sm font-medium text-emerald-300 hover:text-emerald-200">Back to dashboard</a>
                <p class="mt-8 text-sm font-semibold uppercase tracking-wide text-emerald-300">Customer verification</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white">Submit your KYC</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-zinc-300">
                    Wallet Yanga needs your identity and source-of-funds details before deposits, USD conversion, and card payments can be enabled.
                </p>

                <div class="mt-6 rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                    <p class="text-sm text-zinc-400">Current status</p>
                    <p class="mt-2 text-xl font-semibold capitalize">{{ str_replace('_', ' ', $kyc->status) }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold">Identity Details</h2>
                    <p class="mt-1 text-sm text-zinc-400">Use details that match your official document.</p>
                </div>

                <form method="POST" action="{{ route('kyc.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="date_of_birth" class="text-sm font-medium text-zinc-200">Date of birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($kyc->date_of_birth)->format('Y-m-d')) }}" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            @error('date_of_birth')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="identity_type" class="text-sm font-medium text-zinc-200">Document type</label>
                            <select id="identity_type" name="identity_type" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                                @foreach ($identityTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('identity_type', $kyc->identity_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('identity_type')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="identity_number" class="text-sm font-medium text-zinc-200">National ID/passport number</label>
                        <input id="identity_number" name="identity_number" value="{{ old('identity_number', $kyc->identity_number) }}" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                        @error('identity_number')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="address" class="text-sm font-medium text-zinc-200">Residential address</label>
                        <input id="address" name="address" value="{{ old('address', $kyc->address) }}" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                        @error('address')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="city_district" class="text-sm font-medium text-zinc-200">City/district</label>
                            <input id="city_district" name="city_district" value="{{ old('city_district', $kyc->city_district) }}" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            @error('city_district')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="occupation" class="text-sm font-medium text-zinc-200">Occupation</label>
                            <input id="occupation" name="occupation" value="{{ old('occupation', $kyc->occupation) }}" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            @error('occupation')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="source_of_funds" class="text-sm font-medium text-zinc-200">Source of funds</label>
                        <select id="source_of_funds" name="source_of_funds" required class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            @foreach ($sourcesOfFunds as $value => $label)
                                <option value="{{ $value }}" @selected(old('source_of_funds', $kyc->source_of_funds) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('source_of_funds')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="id_document" class="text-sm font-medium text-zinc-200">ID document</label>
                        <input id="id_document" type="file" name="id_document" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 w-full rounded-md border border-dashed border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-300 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-800 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-100 hover:border-zinc-500">
                        <p class="mt-1 text-xs text-zinc-500">Upload a JPG, PNG, or PDF up to 5MB. {{ $kyc->id_document_path ? 'A document is already on file.' : '' }}</p>
                        @error('id_document')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                        Submit KYC
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
