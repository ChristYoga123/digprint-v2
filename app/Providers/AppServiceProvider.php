<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentShield::configurePermissionIdentifierUsing(
            function ($resource) {
                // Ambil nama class terakhir, contoh: "BahanKategoriResource"
                $className = class_basename($resource);
                
                // Hapus "Resource" suffix jika ada
                $className = preg_replace('/Resource$/', '', $className);
                
                // Split by uppercase letters
                $parts = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);
                
                if (count($parts) > 1) {
                    // Multiple words: join with "::" (sama seperti getResourcePermissionPrefix)
                    return implode('::', array_map(fn($part) => Str::snake($part), $parts));
                }
                
                // Single word: just convert to snake_case
                return Str::snake($className);
            }
        );

        Model::preventLazyLoading(!app()->isProduction());
        Model::preventAccessingMissingAttributes(!app()->isProduction());
        Model::automaticallyEagerLoadRelationships();
    }
}
