<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StokOpname;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaporanStokOpnamePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_laporan::stok::opname');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StokOpname $stokOpname): bool
    {
        return $user->can('view_laporan::stok::opname');
    }

    /**
     * Determine whether the user can export.
     */
    public function export(User $user): bool
    {
        return $user->can('export_laporan::stok::opname');
    }
}
