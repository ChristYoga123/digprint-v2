<?php

namespace App\Models;

use App\Enums\StokOpname\ItemStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnameItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => ItemStatusEnum::class,
        'stock_system' => 'decimal:4',
        'stock_physical' => 'decimal:4',
        'difference' => 'decimal:4',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the stok opname this item belongs to
     */
    public function stokOpname(): BelongsTo
    {
        return $this->belongsTo(StokOpname::class);
    }

    /**
     * Get the bahan for this item
     */
    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class);
    }

    /**
     * Get the user who approved this item
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the histories for this item
     */
    public function histories(): HasMany
    {
        return $this->hasMany(StokOpnameHistory::class);
    }

    /**
     * Calculate and set the difference
     */
    public function calculateDifference(): void
    {
        if ($this->stock_physical !== null) {
            $this->difference = $this->stock_physical - $this->stock_system;
            $this->save();
        }
    }

    /**
     * Check if there is a difference
     */
    public function hasDifference(): bool
    {
        return $this->difference !== null && $this->difference != 0;
    }

    /**
     * Get difference type (more, less, or equal)
     */
    public function getDifferenceTypeAttribute(): string
    {
        if ($this->difference === null) {
            return 'unknown';
        }

        if ($this->difference > 0) {
            return 'more';
        } elseif ($this->difference < 0) {
            return 'less';
        }

        return 'equal';
    }
}
