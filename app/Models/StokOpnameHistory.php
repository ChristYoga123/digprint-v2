<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnameHistory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'adjustment' => 'decimal:4',
        'adjusted_at' => 'datetime',
    ];

    /**
     * Get the stok opname item this history belongs to
     */
    public function stokOpnameItem(): BelongsTo
    {
        return $this->belongsTo(StokOpnameItem::class);
    }

    /**
     * Get the bahan for this history
     */
    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class);
    }

    /**
     * Get the user who made the adjustment
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Check if this is an increase
     */
    public function isIncrease(): bool
    {
        return $this->adjustment_type === 'increase';
    }

    /**
     * Check if this is a decrease
     */
    public function isDecrease(): bool
    {
        return $this->adjustment_type === 'decrease';
    }
}
