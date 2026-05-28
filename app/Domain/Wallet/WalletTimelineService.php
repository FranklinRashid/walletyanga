<?php

namespace App\Domain\Wallet;

use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WalletTimelineService
{
    public function forUser(User $user, int $limit = 20): Collection
    {
        return LedgerTransaction::query()
            ->whereHas('entries.account', fn ($query) => $query->where('user_id', $user->id))
            ->with('entries.account')
            ->latest('posted_at')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (LedgerTransaction $transaction): array => $this->item($transaction, $user));
    }

    private function item(LedgerTransaction $transaction, User $user): array
    {
        $entries = $transaction->entries
            ->filter(fn (LedgerEntry $entry): bool => (int) $entry->account?->user_id === (int) $user->id)
            ->values();

        return match ($transaction->type) {
            'deposit' => $this->deposit($transaction, $entries),
            'fx_conversion' => $this->conversion($transaction, $entries),
            'card_top_up' => $this->cardTopUp($transaction, $entries),
            'card_authorization' => $this->cardAuthorization($transaction, $entries),
            'card_capture' => $this->cardCapture($transaction, $entries),
            default => $this->generic($transaction, $entries),
        };
    }

    private function deposit(LedgerTransaction $transaction, Collection $entries): array
    {
        $credit = $this->entry($entries, 'credit');

        return $this->base($transaction, [
            'title' => 'Deposit credited',
            'subtitle' => 'Money added to your wallet',
            'amount_label' => $this->signedAmount('+', $credit),
            'direction' => 'in',
            'tone' => 'emerald',
        ]);
    }

    private function conversion(LedgerTransaction $transaction, Collection $entries): array
    {
        $debit = $this->entry($entries, 'debit', 'MWK');
        $credit = $this->entry($entries, 'credit', 'USD');

        return $this->base($transaction, [
            'title' => 'Currency converted',
            'subtitle' => $this->formatEntry($debit).' to '.$this->formatEntry($credit),
            'amount_label' => $this->signedAmount('+', $credit),
            'direction' => 'exchange',
            'tone' => 'sky',
        ]);
    }

    private function cardTopUp(LedgerTransaction $transaction, Collection $entries): array
    {
        $credit = $entries
            ->first(fn (LedgerEntry $entry): bool => $entry->direction === 'credit' && str_contains($entry->account?->code ?? '', ':card_'));

        return $this->base($transaction, [
            'title' => 'Card topped up',
            'subtitle' => 'Moved from USD wallet to card',
            'amount_label' => $this->signedAmount('+', $credit),
            'direction' => 'transfer',
            'tone' => 'violet',
        ]);
    }

    private function cardAuthorization(LedgerTransaction $transaction, Collection $entries): array
    {
        $debit = $entries
            ->first(fn (LedgerEntry $entry): bool => $entry->direction === 'debit' && str_contains($entry->account?->code ?? '', ':card_'));

        return $this->base($transaction, [
            'title' => 'Card payment authorized',
            'subtitle' => $transaction->metadata['merchant_name'] ?? 'Card spend reserved',
            'amount_label' => $this->signedAmount('-', $debit),
            'direction' => 'out',
            'tone' => 'amber',
        ]);
    }

    private function cardCapture(LedgerTransaction $transaction, Collection $entries): array
    {
        $debit = $this->entry($entries, 'debit', 'USD');

        return $this->base($transaction, [
            'title' => 'Card payment settled',
            'subtitle' => 'Reserved card spend settled',
            'amount_label' => $this->signedAmount('-', $debit),
            'direction' => 'out',
            'tone' => 'zinc',
        ]);
    }

    private function generic(LedgerTransaction $transaction, Collection $entries): array
    {
        $entry = $this->entry($entries, 'credit') ?? $this->entry($entries, 'debit');
        $sign = $entry?->direction === 'debit' ? '-' : '+';

        return $this->base($transaction, [
            'title' => Str::headline($transaction->type),
            'subtitle' => $transaction->status,
            'amount_label' => $this->signedAmount($sign, $entry),
            'direction' => $sign === '+' ? 'in' : 'out',
            'tone' => 'zinc',
        ]);
    }

    private function base(LedgerTransaction $transaction, array $attributes): array
    {
        return array_merge([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'occurred_at' => $transaction->posted_at ?? $transaction->created_at,
        ], $attributes);
    }

    private function entry(Collection $entries, string $direction, ?string $currency = null): ?LedgerEntry
    {
        return $entries->first(fn (LedgerEntry $entry): bool => $entry->direction === $direction
            && (! $currency || $entry->currency === $currency));
    }

    private function signedAmount(string $sign, ?LedgerEntry $entry): string
    {
        if (! $entry) {
            return '';
        }

        return $sign.number_format($entry->amount_minor / 100, 2).' '.$entry->currency;
    }

    private function formatEntry(?LedgerEntry $entry): string
    {
        if (! $entry) {
            return 'unknown amount';
        }

        return number_format($entry->amount_minor / 100, 2).' '.$entry->currency;
    }
}
