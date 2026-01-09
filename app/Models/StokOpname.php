<?php

namespace App\Models;

use App\Enums\StokOpname\StatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpname extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusEnum::class,
        'tanggal_opname' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the items for this stok opname
     */
    public function items(): HasMany
    {
        return $this->hasMany(StokOpnameItem::class);
    }

    /**
     * Get the user who created this stok opname
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who submitted this stok opname
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who approved this stok opname
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if all items are approved
     */
    public function allItemsApproved(): bool
    {
        return $this->items()->where('status', '!=', 'approved')->count() === 0;
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Get approved items count
     */
    public function getApprovedItemsAttribute(): int
    {
        return $this->items()->where('status', 'approved')->count();
    }

    /**
     * Get pending items count
     */
    public function getPendingItemsAttribute(): int
    {
        return $this->items()->where('status', 'pending')->count();
    }

    /**
     * Get total positive difference (stok lebih)
     */
    public function getTotalPositiveDifferenceAttribute(): float
    {
        return $this->items()
            ->where('difference', '>', 0)
            ->sum('difference') ?? 0;
    }

    /**
     * Get total negative difference (stok kurang)
     */
    public function getTotalNegativeDifferenceAttribute(): float
    {
        return $this->items()
            ->where('difference', '<', 0)
            ->sum('difference') ?? 0;
    }

    /**
     * Get net difference (total selisih)
     */
    public function getNetDifferenceAttribute(): float
    {
        return $this->items()->sum('difference') ?? 0;
    }
}
