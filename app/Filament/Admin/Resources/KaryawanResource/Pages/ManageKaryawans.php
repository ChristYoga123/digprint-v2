<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\KaryawanResource;

class ManageKaryawans extends ManageRecords
{
    protected static string $resource = KaryawanResource::class;

    // Daftar alamat random untuk data dummy
    protected static array $randomAddresses = [
        'Jl. Sudirman No. 123, Jakarta Pusat',
        'Jl. Gatot Subroto No. 45, Jakarta Selatan',
        'Jl. Merdeka Raya No. 67, Surabaya',
        'Jl. Ahmad Yani No. 89, Bandung',
        'Jl. Diponegoro No. 12, Semarang',
        'Jl. Malioboro No. 34, Yogyakarta',
        'Jl. Pemuda No. 56, Malang',
        'Jl. Asia Afrika No. 78, Bandung',
        'Jl. Pahlawan No. 90, Solo',
        'Jl. Veteran No. 11, Surabaya',
        'Jl. Raya Bogor No. 222, Bogor',
        'Jl. Imam Bonjol No. 33, Semarang',
        'Jl. Cut Nyak Dien No. 44, Medan',
        'Jl. Hasanuddin No. 55, Makassar',
        'Jl. RA Kartini No. 66, Denpasar',
    ];

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()->can('import_karyawan'))
                ->processCollectionUsing(function (string $modelClass, Collection $collection) {
                    $imported = 0;
                    $skipped = 0;
                    $errors = [];
                    
                    foreach ($collection as $index => $row) {
                        $rowNumber = $index + 2; // +2 karena index mulai dari 0 dan header di baris 1
                        
                        try {
                            // Parse fields dari row
                            $nama = trim($row['nama'] ?? $row['name'] ?? '');
                            $email = trim($row['email'] ?? '');
                            $nik = trim($row['nik'] ?? '');
                            $roleName = trim($row['role'] ?? $row['roles'] ?? '');
                            $noHp = trim($row['no_hp'] ?? $row['phone'] ?? '');
                            
                            // Skip empty rows
                            if (empty($nama) || empty($email)) {
                                continue;
                            }
                            
                            // Validasi email format
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $errors[] = "Baris {$rowNumber}: Email '{$email}' tidak valid.";
                                continue;
                            }
                            
                            // Cek apakah user sudah ada (berdasarkan email atau NIK)
                            $existingUser = User::where('email', $email);
                            if (!empty($nik)) {
                                $existingUser = $existingUser->orWhere('nik', $nik);
                            }
                            $existingUser = $existingUser->first();
                            
                            if ($existingUser) {
                                $skipped++;
                                continue;
                            }
                            
                            // Generate NIK jika kosong
                            if (empty($nik)) {
                                $nik = 'KRY-' . str_pad(User::max('id') + $imported + 1, 5, '0', STR_PAD_LEFT);
                            }
                            
                            // Generate no_hp random jika kosong
                            if (empty($noHp)) {
                                $noHp = '08' . rand(100000000, 999999999);
                            }
                            
                            // Ambil alamat random
                            $alamat = self::$randomAddresses[array_rand(self::$randomAddresses)];
                            
                            // Buat user baru
                            $user = User::create([
                                'name' => $nama,
                                'email' => $email,
                                'nik' => $nik,
                                'no_hp' => $noHp,
                                'alamat' => $alamat,
                                'password' => bcrypt('password'),
                                'is_active' => true,
                            ]);
                            
                            // Handle role - cari atau buat role baru
                            if (!empty($roleName)) {
                                // Bisa ada multiple roles dipisahkan koma
                                $roleNames = array_map('trim', explode(',', $roleName));
                                
                                foreach ($roleNames as $rn) {
                                    if (empty($rn)) continue;
                                    
                                    // Cari role yang sudah ada (case-insensitive)
                                    $role = Role::whereRaw('LOWER(name) = ?', [strtolower($rn)])->first();
                                    
                                    // Jika tidak ada, buat role baru
                                    if (!$role) {
                                        $role = Role::create([
                                            'name' => $rn,
                                            'guard_name' => 'web',
                                        ]);
                                    }
                                    
                                    // Assign role ke user
                                    $user->assignRole($role);
                                }
                            }
                            
                            $imported++;
                            
                        } catch (\Exception $e) {
                            $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                        }
                    }
                    
                    // Tampilkan notifikasi hasil import
                    $messages = [];
                    if ($imported > 0) {
                        $messages[] = "{$imported} karyawan baru";
                    }
                    if ($skipped > 0) {
                        $messages[] = "{$skipped} dilewati (sudah ada)";
                    }
                    
                    if ($imported > 0) {
                        Notification::make()
                            ->success()
                            ->title('Import Berhasil')
                            ->body("Berhasil import: " . implode(", ", $messages) . ". Password default: 'password'")
                            ->send();
                    } elseif ($skipped > 0) {
                        Notification::make()
                            ->info()
                            ->title('Tidak ada karyawan baru')
                            ->body("Semua karyawan sudah terdaftar ({$skipped} dilewati)")
                            ->send();
                    }
                    
                    if (!empty($errors)) {
                        Notification::make()
                            ->warning()
                            ->title('Beberapa baris gagal diimport')
                            ->body(implode("\n", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "\n... dan " . (count($errors) - 5) . " error lainnya" : ''))
                            ->persistent()
                            ->send();
                    }
                    
                    if (empty($messages) && empty($errors)) {
                        Notification::make()
                            ->info()
                            ->title('Tidak ada data yang diimport')
                            ->body('File Excel tidak berisi data yang valid.')
                            ->send();
                    }
                    
                    return $collection;
                }),
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => Auth::user()->can('import_karyawan'))
                ->action(function () {
                    // Generate template Excel dengan header yang dibutuhkan
                    $headers = [
                        'nama',
                        'email',
                        'nik',
                        'no_hp',
                        'role',
                    ];
                    
                    // Contoh data untuk membantu user memahami format
                    $exampleRows = [
                        [
                            'John Doe',
                            'john@example.com',
                            'KRY-00001',
                            '081234567890',
                            'operator',
                        ],
                        [
                            'Jane Smith',
                            'jane@example.com',
                            'KRY-00002',
                            '081234567891',
                            'admin, manager', // Contoh multiple roles
                        ],
                    ];
                    
                    // Buat konten CSV
                    $output = fopen('php://temp', 'r+');
                    fputcsv($output, $headers);
                    foreach ($exampleRows as $row) {
                        fputcsv($output, $row);
                    }
                    rewind($output);
                    $csv = stream_get_contents($output);
                    fclose($output);
                    
                    // Download sebagai file
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'template_import_karyawan.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            Actions\CreateAction::make()
                ->visible(fn () => Auth::user()->can('create_karyawan'))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['password'] = bcrypt('password');
                    return $data;
                })
                ->closeModalByClickingAway(false),
        ];
    }

    public function getTitle(): string
    {
        return 'Karyawan';
    }
}
