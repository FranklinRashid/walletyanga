<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable()->unique();
            $table->string('country', 2)->default('MW');
            $table->string('status')->default('registered');
            $table->timestamp('phone_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('kyc_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->string('tier')->default('basic');
            $table->string('risk_rating')->default('standard');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->json('screening_result')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3);
            $table->string('type')->default('main');
            $table->string('status')->default('active');
            $table->bigInteger('cached_balance_minor')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'currency', 'type']);
        });

        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('currency', 3);
            $table->string('type');
            $table->string('normal_side');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type');
            $table->string('status')->default('posted');
            $table->string('idempotency_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ledger_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->string('direction');
            $table->string('currency', 3);
            $table->bigInteger('amount_minor');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['ledger_account_id', 'currency']);
        });

        Schema::create('deposit_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('paychangu');
            $table->string('tx_ref')->unique();
            $table->string('currency', 3)->default('MWK');
            $table->bigInteger('amount_minor');
            $table->string('status')->default('pending');
            $table->string('checkout_url')->nullable();
            $table->string('provider_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateway_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->default('paychangu');
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('tx_ref')->nullable()->index();
            $table->string('signature')->nullable();
            $table->json('payload');
            $table->string('processing_status')->default('received');
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });

        Schema::create('fx_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->string('source')->default('admin');
            $table->timestamp('captured_at');
            $table->timestamps();
        });

        Schema::create('fx_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->bigInteger('from_amount_minor');
            $table->decimal('rate', 20, 8);
            $table->unsignedInteger('spread_bps')->default(0);
            $table->decimal('effective_rate', 20, 8);
            $table->bigInteger('to_amount_minor');
            $table->timestamp('expires_at');
            $table->string('status')->default('quoted');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('fx_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fx_quote_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('ledger_transaction_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('posted');
            $table->timestamps();
        });

        Schema::create('virtual_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_card_id')->unique();
            $table->string('masked_pan')->nullable();
            $table->string('brand')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active');
            $table->string('nickname')->nullable();
            $table->bigInteger('daily_limit_minor')->nullable();
            $table->bigInteger('monthly_limit_minor')->nullable();
            $table->json('controls')->nullable();
            $table->timestamps();
        });

        Schema::create('card_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('virtual_card_id')->constrained()->cascadeOnDelete();
            $table->string('provider_authorization_id')->unique();
            $table->string('currency', 3)->default('USD');
            $table->bigInteger('amount_minor');
            $table->string('merchant_name')->nullable();
            $table->string('merchant_category')->nullable();
            $table->string('status')->default('authorized');
            $table->foreignId('ledger_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('card_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('virtual_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_authorization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_transaction_id')->unique();
            $table->string('type');
            $table->string('currency', 3)->default('USD');
            $table->bigInteger('amount_minor');
            $table->string('status')->default('posted');
            $table->foreignId('ledger_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('severity')->default('info');
            $table->string('status')->default('open');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('risk_events');
        Schema::dropIfExists('card_transactions');
        Schema::dropIfExists('card_authorizations');
        Schema::dropIfExists('virtual_cards');
        Schema::dropIfExists('fx_conversions');
        Schema::dropIfExists('fx_quotes');
        Schema::dropIfExists('fx_rates');
        Schema::dropIfExists('payment_gateway_events');
        Schema::dropIfExists('deposit_intents');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('ledger_accounts');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('kyc_profiles');
        Schema::dropIfExists('user_profiles');
    }
};
