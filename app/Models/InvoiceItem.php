<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'name',
        'description',
        'quantity',
        'price',
        'tax_rate',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withoutGlobalScope('company');
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
