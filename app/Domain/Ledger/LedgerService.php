<?php

namespace App\Domain\Ledger;

use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LedgerService
{
    /**
     * @param array<int, array{account_code:string,direction:string,currency:string,amount_minor:int,metadata?:array}> $entries
     */
    public function post(string $type, string $idempotencyKey, array $entries, array $metadata = []): LedgerTransaction
    {
        return DB::transaction(function () use ($type, $idempotencyKey, $entries, $metadata): LedgerTransaction {
            $existing = LedgerTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->with('entries')
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->assertBalanced($entries);

            $transaction = LedgerTransaction::query()->create([
                'reference' => 'LYT-'.Str::upper(Str::random(14)),
                'type' => $type,
                'status' => 'posted',
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
                'posted_at' => Carbon::now(),
            ]);

            foreach ($entries as $entry) {
                $account = LedgerAccount::query()
                    ->where('code', $entry['account_code'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->currency !== $entry['currency']) {
                    throw new InvalidArgumentException("Ledger account {$account->code} does not support {$entry['currency']}.");
                }

                $transaction->entries()->create([
                    'ledger_account_id' => $account->id,
                    'direction' => $entry['direction'],
                    'currency' => $entry['currency'],
                    'amount_minor' => $entry['amount_minor'],
                    'metadata' => $entry['metadata'] ?? null,
                ]);

                if ($account->wallet_id) {
                    $this->refreshWalletBalance($account->wallet);
                }
            }

            return $transaction->load('entries.account');
        });
    }

    public function accountBalanceMinor(LedgerAccount $account): int
    {
        $debits = $account->entries()->where('direction', 'debit')->sum('amount_minor');
        $credits = $account->entries()->where('direction', 'credit')->sum('amount_minor');

        return $account->normal_side === 'credit'
            ? (int) ($credits - $debits)
            : (int) ($debits - $credits);
    }

    private function refreshWalletBalance(?Wallet $wallet): void
    {
        if (! $wallet || ! $wallet->ledgerAccount) {
            return;
        }

        $wallet->forceFill([
            'cached_balance_minor' => $this->accountBalanceMinor($wallet->ledgerAccount),
        ])->save();
    }

    /**
     * @param array<int, array{direction:string,currency:string,amount_minor:int}> $entries
     */
    private function assertBalanced(array $entries): void
    {
        $totals = [];

        foreach ($entries as $entry) {
            if ($entry['amount_minor'] <= 0) {
                throw new InvalidArgumentException('Ledger entries must be positive minor-unit amounts.');
            }

            if (! in_array($entry['direction'], ['debit', 'credit'], true)) {
                throw new InvalidArgumentException('Ledger direction must be debit or credit.');
            }

            $currency = $entry['currency'];
            $totals[$currency] ??= ['debit' => 0, 'credit' => 0];
            $totals[$currency][$entry['direction']] += $entry['amount_minor'];
        }

        foreach ($totals as $currency => $total) {
            if ($total['debit'] !== $total['credit']) {
                throw new InvalidArgumentException("Ledger transaction is not balanced for {$currency}.");
            }
        }
    }
}
