<?php

namespace App\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    public function __construct(private readonly ChartOfAccounts $accounts)
    {
    }

    /**
     * Post a balanced journal entry.
     *
     * Each line: ['account' => 'cash', 'debit' => 100, 'credit' => 0,
     *            'description' => null, 'party_type' => null, 'party_id' => null]
     */
    public function post(
        string $eventType,
        string $sourceType,
        ?int $sourceId,
        string $sourceKey,
        $date = null,
        ?string $memo = null,
        array $lines = [],
        ?int $createdBy = null
    ): ?JournalEntry {
        $existing = JournalEntry::withTrashed()->where('source_key', $sourceKey)->first();

        if ($existing) {
            return $existing;
        }

        $normalized = $this->normalize($lines);

        if (count($normalized) < 2) {
            return null;
        }

        $totalDebit = round(array_sum(array_column($normalized, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($normalized, 'credit')), 2);

        if ($totalDebit !== $totalCredit) {
            throw new UnbalancedEntryException(
                "Journal entry [{$sourceKey}] is not balanced: debit {$totalDebit} != credit {$totalCredit}."
            );
        }

        if ($totalDebit <= 0) {
            return null;
        }

        $entryDate = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date ?? now());

        try {
            return DB::transaction(function () use ($eventType, $sourceType, $sourceId, $sourceKey, $entryDate, $memo, $normalized, $createdBy) {
                $entry = JournalEntry::create([
                    'entry_no' => 'TMP-'.Str::uuid(),
                    'date' => $entryDate->toDateString(),
                    'memo' => $memo,
                    'event_type' => $eventType,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'source_key' => $sourceKey,
                    'status' => 'posted',
                    'is_auto' => true,
                    'posted_at' => now(),
                    'created_by' => $createdBy,
                ]);

                $entry->update([
                    'entry_no' => 'JE-'.$entryDate->format('Y').'-'.str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT),
                ]);

                foreach ($normalized as $line) {
                    JournalLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $this->accounts->id($line['account']),
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                        'description' => $line['description'] ?? null,
                        'party_type' => $line['party_type'] ?? null,
                        'party_id' => $line['party_id'] ?? null,
                        'created_at' => now(),
                    ]);
                }

                return $entry->fresh('lines');
            });
        } catch (QueryException $e) {
            $existing = JournalEntry::withTrashed()->where('source_key', $sourceKey)->first();
            if ($existing) {
                return $existing;
            }
            throw $e;
        }
    }

    /**
     * Post the mirror image of an existing entry (used for voids/corrections).
     */
    public function reverse(JournalEntry $entry, ?string $memo = null, ?int $createdBy = null): ?JournalEntry
    {
        $lines = $entry->lines->map(fn (JournalLine $line) => [
            'account_id' => $line->account_id,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
            'description' => $line->description,
            'party_type' => $line->party_type,
            'party_id' => $line->party_id,
        ])->all();

        return DB::transaction(function () use ($entry, $lines, $memo, $createdBy) {
            $reversal = JournalEntry::create([
                'entry_no' => 'TMP-'.Str::uuid(),
                'date' => now()->toDateString(),
                'memo' => $memo ?? "Reversal of {$entry->entry_no}",
                'event_type' => $entry->event_type.'Reversal',
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'source_key' => $entry->source_key.':reversal',
                'status' => 'posted',
                'is_auto' => true,
                'posted_at' => now(),
                'created_by' => $createdBy,
            ]);

            $reversal->update([
                'entry_no' => 'JE-'.now()->format('Y').'-'.str_pad((string) $reversal->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'],
                    'party_type' => $line['party_type'],
                    'party_id' => $line['party_id'],
                    'created_at' => now(),
                ]);
            }

            return $reversal->fresh('lines');
        });
    }

    private function normalize(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            if ($debit < 0 || $credit < 0) {
                throw new UnbalancedEntryException('Journal lines cannot have negative amounts.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new UnbalancedEntryException('A journal line cannot have both a debit and a credit.');
            }

            $normalized[] = [
                'account' => $line['account'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'party_type' => $line['party_type'] ?? null,
                'party_id' => $line['party_id'] ?? null,
            ];
        }

        return $normalized;
    }
}
