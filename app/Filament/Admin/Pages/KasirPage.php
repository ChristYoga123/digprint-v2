<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Transaksi;
use App\Models\TransaksiKalkulasi;
use App\Models\TransaksiProduk;
use App\Models\TransaksiProses;
use App\Models\Produk;
use App\Models\ProdukProses;
use App\Models\PencatatanKeuangan;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms;
use Filament\Support\RawJs;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\Transaksi\JenisDiskonEnum;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Enums\TransaksiProses\StatusProsesEnum;

class KasirPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Kasir';

    public function getTitle(): string|Htmlable
    {
        return 'Kasir';
    }

    protected static string $view = 'filament.admin.pages.kasir-page';

    // State properties
    public ?int $selectedKalkulasiId = null;
    public ?array $selectedKalkulasi = null;
    public array $cartItems = [];
    public array $itemDiscounts = [];
    public ?string $jenisDiskon = null;
    public ?int $totalDiskonInvoice = 0;
    public ?string $statusPembayaran = null;
    public ?string $metodePembayaran = null;
    public ?string $jumlahBayar = '0';
    public ?string $tanggalPembayaran = null;
    public ?string $tanggalJatuhTempo = null;
    public ?string $printNotaSize = 'thermal';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getForms(): array
    {
        return [
            'form',
            'checkoutForm',
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                // Empty schema for the main form
            ])
            ->statePath('formData');
    }

    public function checkoutForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Diskon')
                    ->schema([
                        Forms\Components\TextInput::make('totalDiskonInvoice')
                            ->label('Total Diskon Invoice')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->statePath('totalDiskonInvoice')
                            ->helperText('Diskon untuk seluruh transaksi (opsional)'),
                    ])
                    ->visible(fn () => !empty($this->cartItems))
                    ->collapsible()
                    ->description('Anda bisa memberikan diskon per item di bawah setiap produk, atau diskon invoice di sini, atau keduanya.'),
                Forms\Components\Section::make('Pembayaran')
                    ->schema([
                        Forms\Components\Radio::make('statusPembayaran')
                            ->label('Status Pembayaran')
                            ->options([
                                StatusPembayaranEnum::LUNAS->value => 'Lunas',
                                StatusPembayaranEnum::TERM_OF_PAYMENT->value => 'Term of Payment',
                            ])
                            ->required()
                            ->live()
                            ->statePath('statusPembayaran')
                            ->afterStateUpdated(function ($state) {
                                // Jika LUNAS dipilih, set default jumlah bayar = total setelah diskon
                                if ($state == StatusPembayaranEnum::LUNAS->value) {
                                    $this->jumlahBayar = (string) $this->getCartTotalAfterDiscount();
                                } else {
                                    // Jika TOP dipilih, reset jumlah bayar ke 0
                                    $this->jumlahBayar = '0';
                                }
                            }),
                        Forms\Components\Select::make('metodePembayaran')
                            ->label('Metode Pembayaran')
                            ->options(getBankData())
                            ->searchable()
                            ->required(fn () => $this->statusPembayaran == StatusPembayaranEnum::LUNAS->value)
                            ->statePath('metodePembayaran')
                            ->helperText(fn () => $this->statusPembayaran == StatusPembayaranEnum::TERM_OF_PAYMENT->value ? 'Opsional, isi jika pembayaran TOP memiliki metode tertentu' : null)
                            ->visible(fn () => in_array($this->statusPembayaran, [
                                StatusPembayaranEnum::LUNAS->value,
                                StatusPembayaranEnum::TERM_OF_PAYMENT->value,
                            ])),
                        Forms\Components\TextInput::make('jumlahBayar')
                            ->label(fn () => $this->statusPembayaran == StatusPembayaranEnum::LUNAS->value ? 'Jumlah Bayar' : 'Jumlah Bayar (Partial)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(fn () => $this->statusPembayaran == StatusPembayaranEnum::LUNAS->value)
                            ->minValue(fn () => $this->statusPembayaran == StatusPembayaranEnum::LUNAS->value ? $this->getCartTotalAfterDiscount() : 0)
                            ->live(onBlur: true)
                            ->statePath('jumlahBayar')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->afterStateUpdated(function ($state) {
                                // Parse dan simpan nilai sebagai string (tanpa separator)
                                // Mask money akan memformat tampilan, tapi kita simpan sebagai string tanpa separator
                                if (empty($state)) {
                                    $this->jumlahBayar = '0';
                                    return;
                                }
                                
                                // Parse nilai menjadi int dulu, lalu simpan sebagai string
                                $parsed = (int) str_replace(['.', ','], '', (string) $state);
                                $this->jumlahBayar = (string) $parsed;
                            })
                            ->helperText(function () {
                                if ($this->statusPembayaran == StatusPembayaranEnum::LUNAS->value) {
                                    return 'Minimal: ' . formatRupiah($this->getCartTotalAfterDiscount());
                                }
                                return 'Bisa dibayar secara bertahap. Bisa 0 jika menyusul besoknya.';
                            }),
                        Forms\Components\DatePicker::make('tanggalPembayaran')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now())
                            ->statePath('tanggalPembayaran')
                            ->visible(fn () => $this->statusPembayaran == StatusPembayaranEnum::LUNAS->value),
                        Forms\Components\DatePicker::make('tanggalJatuhTempo')
                            ->label('Tanggal Jatuh Tempo')
                            ->required()
                            ->minDate(now())
                            ->statePath('tanggalJatuhTempo')
                            ->visible(fn () => $this->statusPembayaran == StatusPembayaranEnum::TERM_OF_PAYMENT->value),
                    ])
                    ->visible(fn () => !empty($this->cartItems))
                    ->collapsible(),
                Forms\Components\Section::make('Cetak Nota')
                    ->schema([
                        Forms\Components\Select::make('printNotaSize')
                            ->label('Ukuran Kertas')
                            ->options([
                                'thermal' => '📱 Thermal (80mm)',
                                'a5' => '📄 A5',
                                'a4' => '📑 A4',
                            ])
                            ->default('thermal')
                            ->required()
                            ->statePath('printNotaSize')
                            ->helperText('Halaman print nota akan otomatis terbuka setelah checkout'),
                    ])
                    ->visible(fn () => !empty($this->cartItems))
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TransaksiKalkulasi::query()
                    ->with(['customer', 'transaksiKalkulasiProduks'])
                    ->whereDoesntHave('transaksis')
                    ->latest()
            )
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode Kalkulasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR')
                    ->getStateUsing(function (TransaksiKalkulasi $record) {
                        // Pastikan relationship sudah di-load
                        if (!$record->relationLoaded('transaksiKalkulasiProduks')) {
                            $record->load('transaksiKalkulasiProduks');
                        }
                        return $record->transaksiKalkulasiProduks->sum('total_harga_produk');
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('pilih')
                    ->label('Pilih')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->action(function (TransaksiKalkulasi $record) {
                        $this->selectKalkulasi($record->id);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public function selectKalkulasi($kalkulasiId): void
    {
        $kalkulasi = TransaksiKalkulasi::with([
            'customer',
            'transaksiKalkulasiProduks.produk.produkProses'
        ])->find($kalkulasiId);

        if (!$kalkulasi) {
            Notification::make()
                ->title('Kalkulasi tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        $this->selectedKalkulasiId = $kalkulasiId;
        $this->selectedKalkulasi = [
            'id' => $kalkulasi->id,
            'kode' => $kalkulasi->kode,
            'customer_id' => $kalkulasi->customer_id,
            'customer_nama' => $kalkulasi->customer->nama,
            'customer_kategori_id' => $kalkulasi->customer->customer_kategori_id,
            'total_harga_kalkulasi' => $kalkulasi->transaksiKalkulasiProduks->sum('total_harga_produk'),
        ];

        $this->cartItems = [];
        foreach ($kalkulasi->transaksiKalkulasiProduks as $item) {
            $this->cartItems[] = [
                'id' => $item->id,
                'produk_id' => $item->produk_id,
                'produk_nama' => $item->produk->nama,
                'judul_pesanan' => $item->judul_pesanan ?? null,
                'jumlah' => $item->jumlah,
                'panjang' => $item->panjang,
                'lebar' => $item->lebar,
                'design_id' => $item->design_id,
                'link_design' => $item->link_design ?? null,
                'addons' => $item->addons ?? [],
                'proses_perlu_sample_approval' => $item->proses_perlu_sample_approval ?? [],
                'total_harga_produk' => $item->total_harga_produk,
                'keterangan' => $item->keterangan ?? null,
            ];
        }

        // Reset form
        $this->totalDiskonInvoice = 0;
        $this->itemDiscounts = [];
        $this->statusPembayaran = null;
        $this->metodePembayaran = null;
        $this->jumlahBayar = '0';
        $this->tanggalPembayaran = null;
        $this->tanggalJatuhTempo = null;

        Notification::make()
            ->title('Kalkulasi berhasil dipilih')
            ->success()
            ->send();
    }

    public function removeFromCart($index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);

        if (empty($this->cartItems)) {
            $this->clearCart();
        }
    }

    public function clearCart(): void
    {
        $this->selectedKalkulasiId = null;
        $this->selectedKalkulasi = null;
        $this->cartItems = [];
        $this->itemDiscounts = [];
        $this->jenisDiskon = null;
        $this->totalDiskonInvoice = 0;
        $this->statusPembayaran = null;
        $this->metodePembayaran = null;
        $this->jumlahBayar = '0';
        $this->tanggalPembayaran = null;
        $this->tanggalJatuhTempo = null;
        $this->printNotaSize = 'thermal';

        Notification::make()
            ->title('Cart dikosongkan')
            ->info()
            ->send();
    }

    public function updateItemDiscount($index, $discount): void
    {
        $this->itemDiscounts[$index] = (int) $discount;
    }

    public function checkout(): void
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Cart masih kosong')
                ->warning()
                ->send();
            return;
        }

        // Validate checkout form
        if (empty($this->statusPembayaran)) {
            Notification::make()
                ->title('Status pembayaran harus diisi')
                ->warning()
                ->send();
            return;
        }

        // Parse jumlah bayar (remove separator: titik dan koma)
        $jumlahBayarParsed = !empty($this->jumlahBayar) ? (int) str_replace(['.', ','], '', (string) $this->jumlahBayar) : 0;
        
        // Validate jumlah bayar
        // Untuk LUNAS, jumlah bayar harus diisi dan >= total setelah diskon
        if ($this->statusPembayaran == StatusPembayaranEnum::LUNAS->value) {
            if (empty($jumlahBayarParsed) || $jumlahBayarParsed < 0) {
                Notification::make()
                    ->title('Jumlah bayar harus diisi')
                    ->warning()
                    ->send();
                return;
            }

            if ($jumlahBayarParsed < $this->getCartTotalAfterDiscount()) {
                Notification::make()
                    ->title('Jumlah bayar tidak boleh kurang dari total')
                    ->warning()
                    ->send();
                return;
            }
        } else {
            // Untuk TOP, jumlah bayar bisa 0 (menyusul), tapi jika diisi harus >= 0
            if (!empty($jumlahBayarParsed) && $jumlahBayarParsed < 0) {
                Notification::make()
                    ->title('Jumlah bayar tidak boleh negatif')
                    ->warning()
                    ->send();
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Calculate subtotal SEBELUM diskon
            $subtotalSebelumDiskon = 0;
            $totalDiskonItem = 0;
            $totalDiskonInvoice = (int) ($this->totalDiskonInvoice ?? 0);
            $transaksiProduks = [];

            foreach ($this->cartItems as $index => $item) {
                $hargaSebelumDiskon = $item['total_harga_produk'];
                $diskonProduk = 0;

                // Apply item discount (jika ada)
                if (isset($this->itemDiscounts[$index])) {
                    $diskonProduk = (int) $this->itemDiscounts[$index];
                    $totalDiskonItem += $diskonProduk;
                }

                $hargaSetelahDiskon = max(0, $hargaSebelumDiskon - $diskonProduk);

                // Normalize addons: pastikan array atau null
                $addons = $item['addons'] ?? null;
                if (is_array($addons) && empty($addons)) {
                    $addons = null;
                }

                $transaksiProduks[] = [
                    'produk_id' => $item['produk_id'],
                    'judul_pesanan' => $item['judul_pesanan'] ?? null,
                    'jumlah' => $item['jumlah'],
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'design_id' => $item['design_id'] ?? null,
                    'link_design' => $item['link_design'] ?? null,
                    'addons' => $addons,
                    'proses_perlu_sample_approval' => $item['proses_perlu_sample_approval'] ?? [],
                    'keterangan' => $item['keterangan'] ?? null,
                    'total_harga_produk_sebelum_diskon' => $hargaSebelumDiskon,
                    'total_diskon_produk' => $diskonProduk,
                    'total_harga_produk_setelah_diskon' => $hargaSetelahDiskon,
                ];

                $subtotalSebelumDiskon += $hargaSebelumDiskon;
            }

            // Total diskon = diskon per item + diskon invoice
            $totalDiskonTransaksi = $totalDiskonItem + $totalDiskonInvoice;

            // Calculate total setelah diskon
            $totalHargaTransaksiSetelahDiskon = max(0, $subtotalSebelumDiskon - $totalDiskonTransaksi);

            // Parse jumlah bayar (remove separator: titik dan koma)
            $jumlahBayarParsed = !empty($this->jumlahBayar) ? (int) str_replace(['.', ','], '', (string) $this->jumlahBayar) : 0;
            
            // Calculate kembalian (hanya jika LUNAS dan jumlah bayar > total setelah diskon)
            $jumlahKembalian = 0;
            if ($this->statusPembayaran == StatusPembayaranEnum::LUNAS->value && $jumlahBayarParsed > $totalHargaTransaksiSetelahDiskon) {
                $jumlahKembalian = $jumlahBayarParsed - $totalHargaTransaksiSetelahDiskon;
            }

            // Create Transaksi
            $transaksi = Transaksi::create([
                'kode' => date('Ymd') . '-' . generateKode('TRX'),
                'transaksi_kalkulasi_id' => $this->selectedKalkulasiId,
                'customer_id' => $this->selectedKalkulasi['customer_id'],
                'total_harga_transaksi' => $subtotalSebelumDiskon,
                'jenis_diskon' => ($totalDiskonItem > 0 && $totalDiskonInvoice > 0) ? 'Kombinasi' : ($totalDiskonItem > 0 ? 'Per Item' : ($totalDiskonInvoice > 0 ? 'Per Invoice' : null)),
                'total_diskon_transaksi' => $totalDiskonTransaksi > 0 ? $totalDiskonTransaksi : null,
                'total_harga_transaksi_setelah_diskon' => $totalHargaTransaksiSetelahDiskon,
                'approved_diskon_by' => null, // Harus di-approve di PengajuanDiskonResource
                'status_transaksi' => StatusTransaksiEnum::BELUM->value,
                'status_pembayaran' => $this->statusPembayaran,
                'metode_pembayaran' => $this->metodePembayaran ?? null,
                'jumlah_bayar' => $jumlahBayarParsed,
                'jumlah_kembalian' => $jumlahKembalian,
                'tanggal_pembayaran' => $this->tanggalPembayaran ?? null,
                'tanggal_jatuh_tempo' => $this->tanggalJatuhTempo ?? null,
                'created_by' => Auth::id(), // Track siapa yang checkout transaksi
            ]);

            // Create PencatatanKeuangan untuk jumlah bayar (hanya jika jumlah bayar > 0)
            // Untuk TOP yang jumlah bayar 0, tidak perlu dibuat pencatatan keuangan (menyusul besoknya)
            if ($jumlahBayarParsed > 0) {
                PencatatanKeuangan::create([
                    'pencatatan_keuangan_type' => Transaksi::class,
                    'pencatatan_keuangan_id' => $transaksi->id,
                    'user_id' => Auth::id(),
                    'jumlah_bayar' => $jumlahBayarParsed,
                    'metode_pembayaran' => $this->metodePembayaran ?? null,
                    'keterangan' => 'Pembayaran transaksi ' . $transaksi->kode,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            }

            // Update jumlah_bayar di Transaksi dari aggregate pencatatan_keuangans
            $totalPembayaran = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                ->where('pencatatan_keuangan_id', $transaksi->id)
                ->sum('jumlah_bayar');

            // Update jumlah_kembalian untuk LUNAS (hanya jika jumlah bayar > total setelah diskon)
            $jumlahKembalianUpdated = 0;
            if ($this->statusPembayaran == StatusPembayaranEnum::LUNAS->value && $totalPembayaran > $totalHargaTransaksiSetelahDiskon) {
                $jumlahKembalianUpdated = $totalPembayaran - $totalHargaTransaksiSetelahDiskon;
            }

            $transaksi->update([
                'jumlah_bayar' => $totalPembayaran,
                'jumlah_kembalian' => $jumlahKembalianUpdated,
            ]);

            // Jika TOP, cek apakah sudah lunas (total pembayaran >= total setelah diskon)
            if ($this->statusPembayaran == StatusPembayaranEnum::TERM_OF_PAYMENT->value) {
                // Jika total pembayaran sudah >= total setelah diskon, update status menjadi LUNAS
                if ($totalPembayaran >= $totalHargaTransaksiSetelahDiskon) {
                    $transaksi->update([
                        'status_pembayaran' => StatusPembayaranEnum::LUNAS->value,
                        'tanggal_pembayaran' => $this->tanggalPembayaran ?? now(),
                        'jumlah_kembalian' => 0, // TOP tidak ada kembalian
                    ]);
                }
            }

            // Create TransaksiProduks and TransaksiProses
            $linkDesigns = []; // Kumpulkan semua link_design untuk disimpan di transaksis
            foreach ($transaksiProduks as $produkData) {
                // Normalize addons: jika empty array, set menjadi null
                $addons = $produkData['addons'] ?? null;
                if (is_array($addons) && empty($addons)) {
                    $addons = null;
                }
                
                // Kumpulkan link_design jika ada
                if (!empty($produkData['link_design'])) {
                    $linkDesigns[] = $produkData['link_design'];
                }
                
                $transaksiProduk = TransaksiProduk::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produkData['produk_id'],
                    'judul_pesanan' => $produkData['judul_pesanan'] ?? null,
                    'jumlah' => $produkData['jumlah'],
                    'panjang' => $produkData['panjang'],
                    'lebar' => $produkData['lebar'],
                    'design_id' => $produkData['design_id'] ?? null,
                    'link_design' => $produkData['link_design'] ?? null,
                    'addons' => $addons,
                    'keterangan' => $produkData['keterangan'] ?? null,
                    'total_harga_produk_sebelum_diskon' => $produkData['total_harga_produk_sebelum_diskon'],
                    'total_diskon_produk' => $produkData['total_diskon_produk'],
                    'total_harga_produk_setelah_diskon' => $produkData['total_harga_produk_setelah_diskon'],
                ]);

                $urutan = 1;
                
                // Ambil data proses yang perlu sample approval dari kalkulasi
                $prosesPerluSampleApproval = $produkData['proses_perlu_sample_approval'] ?? [];
                if (!is_array($prosesPerluSampleApproval)) {
                    $prosesPerluSampleApproval = [];
                }

                // Jika ada design_id, buat proses desain terlebih dahulu dengan urutan 1
                if (!empty($produkData['design_id'])) {
                    // Cek apakah design termasuk dalam proses yang perlu sample approval
                    $perluSample = in_array((int) $produkData['design_id'], $prosesPerluSampleApproval);
                    TransaksiProses::create([
                        'transaksi_produk_id' => $transaksiProduk->id,
                        'produk_proses_id' => $produkData['design_id'],
                        'urutan' => $urutan,
                        'status_proses' => StatusProsesEnum::BELUM->value,
                        'apakah_perlu_sample_approval' => $perluSample,
                    ]);
                    $urutan++;
                }

                // Get ProdukProses for this product (only production processes, not addons)
                $produkProses = ProdukProses::where('produk_id', $produkData['produk_id'])
                    ->where('produk_proses_kategori_id', 2) // Only Produksi category
                    ->whereNotNull('urutan')
                    ->orderBy('urutan')
                    ->get();

                // Tambahkan proses produksi setelah desain (jika ada)
                foreach ($produkProses as $pp) {
                    // Cek apakah proses ini termasuk yang perlu sample approval
                    $perluSample = in_array((int) $pp->id, $prosesPerluSampleApproval);
                    TransaksiProses::create([
                        'transaksi_produk_id' => $transaksiProduk->id,
                        'produk_proses_id' => $pp->id,
                        'urutan' => $urutan,
                        'status_proses' => StatusProsesEnum::BELUM->value,
                        'apakah_perlu_sample_approval' => $perluSample,
                    ]);
                    $urutan++;
                }

                // Add addons as additional proses (after production processes)
                if (!empty($produkData['addons']) && is_array($produkData['addons'])) {
                    foreach ($produkData['addons'] as $addonProdukProsesId) {
                        // Cek apakah addon ini termasuk yang perlu sample approval
                        $perluSample = in_array((int) $addonProdukProsesId, $prosesPerluSampleApproval);
                        TransaksiProses::create([
                            'transaksi_produk_id' => $transaksiProduk->id,
                            'produk_proses_id' => $addonProdukProsesId,
                            'urutan' => $urutan,
                            'status_proses' => StatusProsesEnum::BELUM->value,
                            'apakah_perlu_sample_approval' => $perluSample,
                        ]);
                        $urutan++;
                    }
                }
            }
            
            // Update transaksis dengan link_design jika ada
            if (!empty($linkDesigns)) {
                // Gabungkan semua link_design dengan separator baris baru
                $linkDesignCombined = implode("\n", array_unique($linkDesigns));
                $transaksi->update([
                    'link_design' => $linkDesignCombined
                ]);
            }

            DB::commit();

            // Store transaksi id and print size before clearing cart
            $transaksiIdForPrint = $transaksi->id;
            $printSize = $this->printNotaSize ?? 'thermal';

            Notification::make()
                ->title('Transaksi berhasil dibuat')
                ->success()
                ->send();

            // Clear cart
            $this->clearCart();

            // Redirect to print page in new tab using JavaScript
            $this->js("window.open('" . route('print.nota', ['transaksi_id' => $transaksiIdForPrint, 'size' => $printSize]) . "', '_blank');");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Gagal membuat transaksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getCartTotal(): int
    {
        $total = 0;
        foreach ($this->cartItems as $item) {
            $total += $item['total_harga_produk'];
        }
        return $total;
    }

    public function getCartTotalAfterDiscount(): int
    {
        $total = $this->getCartTotal();

        // Hitung total diskon per item
        $totalDiskonItem = 0;
        foreach ($this->itemDiscounts as $discount) {
            $totalDiskonItem += (int) $discount;
        }

        // Hitung total diskon invoice
        $totalDiskonInvoice = (int) ($this->totalDiskonInvoice ?? 0);

        // Total diskon = diskon per item + diskon invoice
        $totalDiskon = $totalDiskonItem + $totalDiskonInvoice;

        return max(0, $total - $totalDiskon);
    }

    public function getKembalian(): int
    {
        if ($this->statusPembayaran != StatusPembayaranEnum::LUNAS->value) {
            return 0;
        }

        $totalSetelahDiskon = $this->getCartTotalAfterDiscount();
        // Parse jumlah bayar (remove separator: titik dan koma)
        $jumlahBayar = !empty($this->jumlahBayar) ? (int) str_replace(['.', ','], '', (string) $this->jumlahBayar) : 0;

        if ($jumlahBayar > $totalSetelahDiskon) {
            return $jumlahBayar - $totalSetelahDiskon;
        }

        return 0;
    }

    /**
     * Tambah pembayaran untuk transaksi TOP
     */
    public function tambahPembayaran(Transaksi $transaksi, int $jumlahBayar, ?string $keterangan = null): void
    {
        if ($transaksi->status_pembayaran != StatusPembayaranEnum::TERM_OF_PAYMENT->value) {
            Notification::make()
                ->title('Transaksi ini bukan Term of Payment')
                ->warning()
                ->send();
            return;
        }

        if ($jumlahBayar <= 0) {
            Notification::make()
                ->title('Jumlah bayar harus lebih dari 0')
                ->warning()
                ->send();
            return;
        }

        try {
            DB::beginTransaction();

            // Create PencatatanKeuangan
            PencatatanKeuangan::create([
                'pencatatan_keuangan_type' => Transaksi::class,
                'pencatatan_keuangan_id' => $transaksi->id,
                'user_id' => Auth::id(),
                'jumlah_bayar' => $jumlahBayar,
                'keterangan' => $keterangan ?? 'Pembayaran tambahan transaksi ' . $transaksi->kode,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // Update jumlah_bayar di Transaksi dari aggregate pencatatan_keuangans
            $totalPembayaran = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                ->where('pencatatan_keuangan_id', $transaksi->id)
                ->sum('jumlah_bayar');

            $transaksi->update([
                'jumlah_bayar' => $totalPembayaran,
            ]);

            // Cek apakah sudah lunas
            if ($totalPembayaran >= $transaksi->total_harga_transaksi_setelah_diskon) {
                $transaksi->update([
                    'status_pembayaran' => StatusPembayaranEnum::LUNAS->value,
                    'tanggal_pembayaran' => now(),
                    'jumlah_kembalian' => 0, // TOP tidak ada kembalian
                ]);

                Notification::make()
                    ->title('Transaksi sudah lunas')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Pembayaran berhasil ditambahkan')
                    ->success()
                    ->send();
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Gagal menambah pembayaran')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
