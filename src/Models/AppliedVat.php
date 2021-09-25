<?php

namespace IFRS\Models;

use IFRS\Traits\ModelTablePrefix;

use Illuminate\Database\Eloquent\Model;

class AppliedVat extends Model
{
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vat_id',
        'line_item_id',
        'amount',
    ];

    /**
     * LineItem.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lineItem()
    {
        return $this->belongsTo(LineItem::class);
    }

    /**
     * Vat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }
}
