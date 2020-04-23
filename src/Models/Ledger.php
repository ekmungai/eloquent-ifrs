<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Segragatable;

use Ekmungai\IFRS\Traits\Segragating;

/**
 * Class Ledger
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Entity $entity
 * @property Transaction $transaction
 * @property Vat $vat
 * @property Account $postAccount
 * @property Account $folioAccount
 * @property LineItem $lineItem
 * @property Carbon $date
 * @property string $entry_type
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Ledger extends Model implements Segragatable
{
    use Segragating;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entry_type',
    ];

    /**
     * Create VAT Ledger entries for the Transaction LineItem.
     *
     * @param LineItem $lineItem
     * @param Transaction $transaction
     *
     * @return Transaction
     */
    private static function postVat(LineItem $lineItem, Transaction $transaction) : Transaction
    {
        $amount = $lineItem->amount * $lineItem->vat->rate/100;

        $post = new Ledger();
        $folio = new Ledger();

        if (boolval($transaction->credited)) {
            $post->entry_type = Balance::C;
            $folio->entry_type = Balance::D;
        } else {
            $post->entry_type = Balance::D;
            $folio->entry_type = Balance::C;
        }

        // identical double entry data
        $post->transaction_id = $folio->transaction_id = $transaction->id;
        $post->date = $folio->date = $transaction->date;
        $post->line_item_id = $folio->line_item_id = $lineItem->id;
        $post->vat_id = $folio->vat_id = $lineItem->vat_id;
        $post->amount = $folio->amount = $amount;

        // different double entry data
        $post->post_account = $folio->folio_account = $transaction->account_id;
        $post->folio_account = $folio->post_account = $lineItem->vat_account_id;

        $post->save();
        $folio->save();

        $transaction->amount += $amount;

        return $transaction;
    }

    /**
     * Create Ledger entries for the Transaction.
     *
     * @param Transaction $transaction
     */
    public static function post(Transaction $transaction) : void
    {
        //Remove current ledgers if any prior to creating new ones
        $transaction->ledgers()->delete();

        foreach ($transaction->getLineItems() as $lineItem) {
            $post = new Ledger();
            $folio = new Ledger();

            if (boolval($transaction->credited)) {
                $post->entry_type = Balance::C;
                $folio->entry_type = Balance::D;
            } else {
                $post->entry_type = Balance::D;
                $folio->entry_type = Balance::C;
            }

            // identical double entry data
            $post->transaction_id = $folio->transaction_id = $transaction->id;
            $post->date = $folio->date = $transaction->date;
            $post->line_item_id = $folio->line_item_id = $lineItem->id;
            $post->vat_id = $folio->vat_id = $lineItem->vat_id;
            $post->amount = $folio->amount = $lineItem->amount;

            // different double entry data
            $post->post_account = $folio->folio_account = $transaction->account_id;
            $post->folio_account = $folio->post_account = $lineItem->account_id;

            $post->save();
            $folio->save();

            $transaction->amount += $lineItem->amount;

            if ($lineItem->vat->rate > 0) {
                $transaction = Ledger::postVat($lineItem, $transaction);
            }
            $transaction->save();
        }
    }

    /**
     * Ledger attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
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
        return $this->HasOne('Ekmungai\IFRS\Models\Account', 'id', 'post_account');
    }

    /**
     * Ledger Folio Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function folioAccount()
    {
        return $this->HasOne('Ekmungai\IFRS\Models\Account', 'id', 'folio_account');
    }

    /**
     * Ledger LineItem.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lineItem()
    {
        return $this->BelongsTo('Ekmungai\IFRS\Models\LineItem', 'line_item_id', 'id');
    }

    /**
     * Hash Ledger contents
     *
     * @return string
     */
    public function hashed() {
        $ledger = [];

        $ledger[] = $this->entity_id;
        $ledger[] = $this->transaction_id;
        $ledger[] = $this->vat_id;
        $ledger[] = $this->post_account;
        $ledger[] = $this->folio_account;
        $ledger[] = $this->line_item_id;
        $ledger[] = is_string($this->date)? $this->date : $this->date->format('Y-m-d G:H:s');
        $ledger[] = $this->entry_type;
        $ledger[] = $this->amount;
        $ledger[] = $this->created_at;

        $previousLedgerId = $this->id - 1;
        $previousLedger = Ledger::find($previousLedgerId);
        $previousHash = is_null($previousLedger)? env('APP_KEY', 'test application key') : $previousLedger->hash;
        $ledger[] = $previousHash;

        return utf8_encode(implode($ledger));
    }

    /**
     * Add Ledger hash.
     */
    public function save(array $options = []): bool
    {
        parent::save();

        $this->hash = password_hash(
            $this->hashed(),
            config('ifrs')['hashing_algorithm']
        );

        return parent::save();
    }

    /**
     * Get Account's contribution to the Transaction total amount.
     *
     * @param Account $account
     * @param int $transactionId
     *
     * @return float
     */
    public static function contribution(Account $account, int $transactionId) : float
    {
        $contribution = 0;

        $query = Ledger::where([
            "post_account" => $account->id,
            "transaction_id" => $transactionId,
        ]);

        foreach ($query->get() as $record) {
            $amount = $record->amount/$record->transaction->exchangeRate->rate;
            $record->entry_type == Balance::D ? $contribution += $amount : $contribution -= $amount;
        }
        return $contribution;
    }

    /**
     * Get Account's balance as at the given date.
     *
     * @param Account $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return float
     */
    public static function balance(Account $account, Carbon $startDate, Carbon $endDate) : float
    {
        $debits = Ledger::where([
            "post_account" => $account->id,
            "entry_type" => Balance::D,
        ])
        ->where("date", ">=", $startDate)
        ->where("date", "<=", $endDate)
        ->sum('amount');

        $credits = Ledger::where([
            "post_account" => $account->id,
            "entry_type" => Balance::C,
        ])
        ->where("date", ">=", $startDate)
        ->where("date", "<=", $endDate)
        ->sum('amount');

        return $debits - $credits;
    }
}
