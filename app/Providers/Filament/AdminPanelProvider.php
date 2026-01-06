<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use App\Filament\Admin\Pages\KasirPage;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use App\Filament\Admin\Pages\ProduksiPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Admin\Pages\FinishingPage;
use App\Filament\Admin\Resources\POResource;
use App\Filament\Admin\Pages\PraProduksiPage;
use App\Filament\Admin\Resources\BahanResource;
use App\Filament\Admin\Resources\MesinResource;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Resources\KloterResource;
use App\Filament\Admin\Resources\ProdukResource;
use App\Filament\Admin\Resources\SatuanResource;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\KaryawanResource;
use App\Filament\Admin\Resources\ProduksiResource;
use App\Filament\Admin\Resources\SupplierResource;
use App\Filament\Admin\Resources\DeskprintResource;
use App\Filament\Admin\Resources\FinishingResource;
use App\Filament\Admin\Resources\PettyCashResource;
use App\Filament\Admin\Resources\TransaksiResource;
use App\Filament\Admin\Resources\LaporanHPPResource;
use App\Filament\Admin\Resources\BahanMutasiResource;
use App\Filament\Admin\Resources\PraProduksiResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use FilipFonal\FilamentLogManager\FilamentLogManager;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use App\Filament\Admin\Resources\PengajuanDiskonResource;
use App\Filament\Admin\Resources\PengajuanLemburResource;
use App\Filament\Admin\Resources\CustomerKategoriResource;
use App\Filament\Admin\Resources\PengajuanSubjoinResource;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Filament\Admin\Resources\BahanMutasiFakturResource;
use App\Filament\Admin\Resources\LaporanDPCustomerResource;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Admin\Resources\LaporanKasPemasukanResource;
use App\Filament\Admin\Resources\LaporanKerjaKaryawanResource;
use App\Filament\Admin\Resources\ProdukProsesKategoriResource;
use App\Filament\Admin\Resources\LaporanHutangSupplierResource;
use App\Filament\Admin\Resources\LaporanLemburKaryawanResource;
use App\Filament\Admin\Resources\LaporanPembelianHarianResource;
use App\Filament\Admin\Resources\LaporanPiutangCustomerResource;
use App\Filament\Admin\Resources\LaporanPembayaranSupplierResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentLogManager::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        userMenuLabel: 'Edit Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        navigationGroup: 'Settings', // Sets the navigation group for the My Profile page (default = null)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'edit-profile' // Sets the slug for the profile page (default = 'my-profile')
                    ),
                ])
                ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                    return $builder->groups([
                        NavigationGroup::make('')
                            ->items([
                                ...Dashboard::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Transaksi')
                            ->items([
                                ...PettyCashResource::getNavigationItems(),
                                ...DeskprintResource::getNavigationItems(),
                                ...KasirPage::getNavigationItems(),
                                ...TransaksiResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Pengajuan')
                            ->items([
                                ...PengajuanSubjoinResource::getNavigationItems(),
                                ...PengajuanDiskonResource::getNavigationItems(),
                                ...PengajuanLemburResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Laporan')
                            ->items([
                                ...LaporanKerjaKaryawanResource::getNavigationItems(),
                                ...LaporanLemburKaryawanResource::getNavigationItems(),
                                ...LaporanPembelianHarianResource::getNavigationItems(),
                                ...LaporanHutangSupplierResource::getNavigationItems(),
                                ...
                                LaporanPembayaranSupplierResource::getNavigationItems(),
                                ...LaporanHPPResource::getNavigationItems(),
                                ...LaporanKasPemasukanResource::getNavigationItems(),
                                ...LaporanPiutangCustomerResource::getNavigationItems(),
                                // ...LaporanDPCustomerResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Produksi')
                            ->items([
                                ...MesinResource::getNavigationItems(),
                                ...ProdukResource::getNavigationItems(),
                                ...KloterResource::getNavigationItems(),
                                ...PraProduksiResource::getNavigationItems(),
                                ...ProduksiResource::getNavigationItems(),
                                ...FinishingResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Gudang')
                            ->items([
                                ...SatuanResource::getNavigationItems(),
                                ...BahanResource::getNavigationItems(),
                                ...POResource::getNavigationItems(),
                                ...BahanMutasiResource::getNavigationItems(),
                                ...BahanMutasiFakturResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Administrasi')
                            ->items([
                                ...KaryawanResource::getNavigationItems(),
                                ...SupplierResource::getNavigationItems(),
                                ...CustomerResource::getNavigationItems(),
                                NavigationItem::make('Roles & Permissions')
                                    ->icon('heroicon-s-shield-check')
                                    ->visible(fn() => auth()->user()->can('view_role') && auth()->user()->can('view_any_role'))
                                    ->url(fn() => route('filament.admin.resources.shield.roles.index'))
                                    ->isActiveWhen(fn() => request()->routeIs('filament.admin.resources.shield.roles.*')),
                            ]),
                        NavigationGroup::make('Master')
                            ->items([
                                ...CustomerKategoriResource::getNavigationItems(),
                                ...ProdukProsesKategoriResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Sistem')
                            ->items([
                                NavigationItem::make('Log Manager')
                                    ->icon('heroicon-s-document-text')
                                    ->visible(fn() => auth()->user()->can('page_Logs'))
                                    ->url(fn() => route('filament.admin.pages.logs'))
                                    ->isActiveWhen(fn() => request()->routeIs('filament.admin.pages.log-manager.*')),
                                NavigationItem::make('Monitoring')
                                    ->icon('heroicon-s-computer-desktop')
                                    ->visible(fn() => auth()->user()->can('page_PulseDashboard'))
                                    ->url(fn() => route('pulse'))
                                    ->isActiveWhen(fn() => request()->routeIs('pulse')),
                            ]),
                    ]);
                })
                ->databaseTransactions()
                ->sidebarCollapsibleOnDesktop();
    }
}
