<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

/**
 * Class Ledger
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Transaction $transaction
 * @property Vat $vat
 * @property Account $postAccount
 * @property Account $folioAccount
 * @property LineItem $lineItem
 * @property Currency $currency
 * @property Carbon $postingDate
 * @property string $entryType
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Ledger extends Model implements Segregatable
{
    use Segregating;
    use SoftDeletes;
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Get Ledger pairs and assign the proper entry types
     *
     * @param LineItem    $lineItem
     * @param Transaction $transaction
     *
     * @return array
     */
    private static function getLedgers(LineItem $lineItem, Transaction $transaction): array
    {

        $post = new Ledger();
        $folio = new Ledger();

        if ($transaction->is_credited) {
            $post->entry_type = Balance::CREDIT;
            $folio->entry_type = Balance::DEBIT;
        } else {
            $post->entry_type = Balance::DEBIT;
            $folio->entry_type = Balance::CREDIT;
        }

        $post->entity_id = $transaction->entity_id;
        $folio->entity_id = $transaction->entity_id;

        return [$post, $folio];
    }

    /**
     * Create Ledger entries for the Transaction.
     *
     * @param Transaction $transaction
     */
    public static function post(Transaction $transaction): void
    {
        //Remove current ledgers if any prior to creating new ones (prevents bypassing Posted Transaction Exception)
        $transaction->ledgers()->delete();

        //get entity from transaction object
        $entity = $transaction->entity;

        foreach ($transaction->getLineItems() as $lineItem) {
            $rate = $transaction->exchangeRate->rate;

            $post = new Ledger();
            $folio = new Ledger();

            list($post, $folio) = Ledger::getLedgers($lineItem, $transaction);

            // identical double entry data
            $post->transaction_id = $folio->transaction_id = $transaction->id;
            $post->currency_id = $folio->currency_id = $transaction->currency_id;
            $post->posting_date = $folio->posting_date = $transaction->transaction_date;
            $post->line_item_id = $folio->line_item_id = $lineItem->id;
            $post->vat_id = $folio->vat_id = $lineItem->vat_id;
            $post->amount = $folio->amount = $lineItem->amount * $rate * $lineItem->quantity;
            $post->rate = $folio->rate = $rate;

            // different double entry data
            $post->post_account = $folio->folio_account = $transaction->account_id;
            $post->folio_account = $folio->post_account = $lineItem->account_id;

            $post->save();
            $folio->save();

            if ($lineItem->vat->rate > 0) {
                Ledger::postVat($lineItem, $transaction);
            }

            // reload ledgers to reflect changes
            $transaction->load('ledgers');
        }
    }

    /**
     * Add Ledger hash.
     */
    public function save(array $options = []): bool
    {
        parent::save();

        $this->hash = hash(config('ifrs')['hashing_algorithm'], $this->hashed());

        return parent::save();
    }

    /**
     * Hash Ledger contents
     *
     * @return string
     */
    public function hashed()
    {
        $ledger = [];

        $ledger[] = $this->entity_id;
        $ledger[] = $this->transaction_id;
        $ledger[] = $this->currency_id;
        $ledger[] = $this->vat_id;
        $ledger[] = $this->post_account;
        $ledger[] = $this->folio_account;
        $ledger[] = $this->line_item_id;
        $ledger[] = Carbon::parse($this->posting_date);
        $ledger[] = $this->entry_type;
        $ledger[] = floatval($this->amount);
        $ledger[] = $this->created_at;

        $previousLedgerId = $this->id - 1;
        $previousLedger = Ledger::find($previousLedgerId);
        $previousHash = is_null($previousLedger) ? env('APP_KEY', 'test application key') : $previousLedger->hash;
        $ledger[] = $previousHash;
        return utf8_encode(implode($ledger));
    }

    /**
     * Create VAT Ledger entries for the Transaction LineItem.
     *
     * @param LineItem $lineItem
     * @param Transaction $transaction
     *
     * @return void
     */
    private static function postVat(LineItem $lineItem, Transaction $transaction): void
    {

        $amount = $lineItem->vat_inclusive ? $lineItem->amount - ($lineItem->amount / (1 + ($lineItem->vat->rate / 100))) : $lineItem->amount * $lineItem->vat->rate / 100;
        $rate = $transaction->exchangeRate->rate;

        list($post, $folio) = Ledger::getLedgers($lineItem, $transaction);

        if ($transaction->is_credited) {
            $post->entry_type = Balance::CREDIT;
            $folio->entry_type = Balance::DEBIT;
        } else {
            $post->entry_type = Balance::DEBIT;
            $folio->entry_type = Balance::CREDIT;
        }

        // identical double entry data
        $post->transaction_id = $folio->transaction_id = $transaction->id;
        $post->currency_id = $folio->currency_id = $transaction->currency_id;
        $post->posting_date = $folio->posting_date = $transaction->transaction_date;
        $post->line_item_id = $folio->line_item_id = $lineItem->id;
        $post->vat_id = $folio->vat_id = $lineItem->vat_id;
        $post->amount = $folio->amount = $amount * $rate * $lineItem->quantity;
        $post->rate = $folio->rate = $rate;

        // different double entry data
        $post->post_account = $folio->folio_account = $lineItem->vat_inclusive ? $lineItem->account_id : $transaction->account_id;
        $post->folio_account = $folio->post_account = $lineItem->vat->account_id;

        $post->save();
        $folio->save();
    }

    /**
     * Create Ledger entries for the Assignments' Forex differences.
     *
     * @param Assignment $assignment
     * @param float $transactionRate
     * @param float $clearedRate
     */
    public static function postForex(Assignment $assignment, $transactionRate, $clearedRate): void
    {
        $rateDifference = round($transactionRate - $clearedRate, config('ifrs.forex_scale'));
        $transaction = $assignment->transaction;

        //get entity from transaction object
        $entity = $transaction->entity;

        $post = new Ledger();
        $folio = new Ledger();

        $post->entity_id = $entity->id;
        $folio->entity_id = $entity->id;

        if ($transaction->is_credited && $rateDifference < 0 || !$transaction->is_credited && $rateDifference > 0) {
            $post->entry_type = Balance::CREDIT;
            $folio->entry_type = Balance::DEBIT;
        } elseif ($transaction->is_credited && $rateDifference > 0 || !$transaction->is_credited && $rateDifference < 0) {
            $post->entry_type = Balance::DEBIT;
            $folio->entry_type = Balance::CREDIT;
        }

        // identical double entry data
        $post->transaction_id = $folio->transaction_id = $transaction->id;
        $post->currency_id = $folio->currency_id = $assignment->transaction->entity->reporting_currency->id;
        $post->posting_date = $folio->posting_date = $assignment->assignment_date;
        $post->amount = $folio->amount = abs($rateDifference) * $assignment->amount;

        // different double entry data
        $post->post_account = $folio->folio_account = $transaction->account_id;
        $post->folio_account = $folio->post_account = $assignment->forex_account_id;

        $post->save();
        $folio->save();
    }

    /**
     * Get Account's contribution to the Transaction total amount.
     *
     * @param Account $account
     * @param int $transactionId
     * @param int $currencyId
     *
     * @return float
     */
    public static function contribution(Account $account, int $transactionId, int $currencyId = null): float
    {
        $ledger = new Ledger();

        $baseQuery = is_null($currencyId) ? $ledger->newQuery()->selectRaw("SUM(amount) AS amount")
            : $ledger->newQuery()->selectRaw("SUM(amount/rate) AS amount");

        $baseQuery->from($ledger->getTable())->where([
            "post_account" => $account->id,
            "transaction_id" => $transactionId,
        ]);

        $cloneQuery = clone $baseQuery;

        $debits = $baseQuery->where("entry_type", Balance::DEBIT);
        $credits = $cloneQuery->where("entry_type", Balance::CREDIT);

        return $debits->get()[0]->amount - $credits->get()[0]->amount;
    }

    /**
     * Get Account's balance as at the given date.
     *
     * @param Account $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $currencyId
     *
     * @return array
     */
    public static function balance(Account $account, Carbon $startDate, Carbon $endDate, int $currencyId = null): array
    {
        $ledger = new Ledger();
        $entity = $account->entity;

        $balances = [$entity->currency_id => 0];

        $baseQuery = $ledger->newQuery()->selectRaw("SUM(amount) AS local_amount, SUM(amount/rate) AS amount");

        $baseQuery->where("post_account", $account->id)
            ->where("posting_date", ">=", $startDate)
            ->where("posting_date", "<=", $endDate);

        if (!is_null($currencyId)) {
            $baseQuery->where("currency_id", $currencyId);
            $balances[$currencyId] = 0;
        }

        $cloneQuery = clone $baseQuery;

        $debits = $baseQuery->where("entry_type", Balance::DEBIT);
        $credits = $cloneQuery->where("entry_type", Balance::CREDIT);

        $balances[$entity->currency_id] = $debits->get()[0]->local_amount - $credits->get()[0]->local_amount;
        if (!is_null($currencyId)) {
            $baseQuery->where("currency_id", $currencyId);
            $balances[$currencyId] = $debits->get()[0]->amount - $credits->get()[0]->amount;
        }
        return $balances;
    }

    /**
     * Ledger attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object)$this->attributes;
    }

    /**
     * Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Ledger Post Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function postAccount()
    {
        return $this->hasOne(Account::class, 'id', 'post_account');
    }

    /**
     * Ledger Folio Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function folioAccount()
    {
        return $this->hasOne(Account::class, 'id', 'folio_account');
    }

    /**
     * Ledger Folio Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency()
    {
        return $this->hasOne(currency::class, 'id', 'currency_id');
    }

    /**
     * Ledger LineItem.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lineItem()
    {
        return $this->belongsTo(LineItem::class, 'line_item_id', 'id');
    }
}
