<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'name', 'description',
        'quantity', 'unit', 'price', 'tax_rate'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->price;
    }

    public function getTaxAmountAttribute(): float
    {
        return $this->subtotal * ($this->tax_rate / 100);
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount;
    }
}
