<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\KaryawanPekerjaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Filament\Admin\Resources\PengajuanLemburResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Database\Eloquent\Model;

class PengajuanLemburResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = KaryawanPekerjaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Pengajuan Lembur';
    
    protected static ?string $modelLabel = 'Pengajuan Lembur';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_pengajuan::lembur') && Auth::user()->can('view_any_pengajuan::lembur');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_pengajuan::lembur');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_pengajuan::lembur');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_pengajuan::lembur');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_pengajuan::lembur');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_pengajuan::lembur');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_pengajuan::lembur');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve',
            'reject'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('karyawan_id')
                    ->label('Karyawan')
                    ->options(User::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('jam_lembur_mulai')
                    ->label('Jam Lembur Mulai')
                    ->required()
                    ->default(now()),
                Forms\Components\DateTimePicker::make('jam_lembur_selesai')
                    ->label('Jam Lembur Selesai')
                    ->required()
                    ->after('jam_lembur_mulai'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                KaryawanPekerjaan::query()
                    ->where('tipe', TipeEnum::LEMBUR)
                    ->with(['karyawan', 'approvedBy'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('karyawan.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('jam_lembur_mulai')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_lembur_selesai')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        if ($record->jam_lembur_mulai && $record->jam_lembur_selesai) {
                            $diff = Carbon::parse($record->jam_lembur_mulai)
                                ->diff(Carbon::parse($record->jam_lembur_selesai));
                            return $diff->h . ' jam ' . $diff->i . ' menit';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('jam_aktual_mulai')
                    ->label('Aktual Mulai')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('jam_aktual_selesai')
                    ->label('Aktual Selesai')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        if ($record->apakah_diapprove_lembur === null) {
                            return 'Menunggu Approval';
                        } elseif ($record->apakah_diapprove_lembur === true) {
                            return 'Disetujui';
                        } else {
                            return 'Ditolak';
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Menunggu Approval' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Waktu Proses')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Approval',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'pending') {
                            $query->whereNull('apakah_diapprove_lembur');
                        } elseif ($data['value'] === 'approved') {
                            $query->where('apakah_diapprove_lembur', true);
                        } elseif ($data['value'] === 'rejected') {
                            $query->where('apakah_diapprove_lembur', false);
                        }
                    }),
                Tables\Filters\SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->options(User::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                DateRangeFilter::make('created_at')
                    ->label('Tanggal Lembur')
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Lembur')
                    ->modalDescription(fn (KaryawanPekerjaan $record) => 
                        "Setujui lembur untuk {$record->karyawan->name}?"
                    )
                    ->visible(fn (KaryawanPekerjaan $record) => $record->apakah_diapprove_lembur === null && Auth::user()->can('approve_pengajuan::lembur'))
                    ->action(function (KaryawanPekerjaan $record) {
                        $record->update([
                            'apakah_diapprove_lembur' => true,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Lembur Disetujui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan Lembur')
                    ->modalDescription(fn (KaryawanPekerjaan $record) => 
                        "Tolak lembur untuk {$record->karyawan->name}?"
                    )
                    ->visible(fn (KaryawanPekerjaan $record) => $record->apakah_diapprove_lembur === null && Auth::user()->can('reject_pengajuan::lembur'))
                    ->action(function (KaryawanPekerjaan $record) {
                        $record->update([
                            'apakah_diapprove_lembur' => false,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Lembur Ditolak')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (KaryawanPekerjaan $record) => $record->apakah_diapprove_lembur === null && Auth::user()->can('update_pengajuan::lembur')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (KaryawanPekerjaan $record) => $record->apakah_diapprove_lembur === null && Auth::user()->can('delete_pengajuan::lembur')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Setujui Semua')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Semua Pengajuan Lembur')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui semua pengajuan lembur yang dipilih?')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->apakah_diapprove_lembur === null) {
                                    $record->update([
                                        'apakah_diapprove_lembur' => true,
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} pengajuan lembur disetujui")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Tolak Semua')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Semua Pengajuan Lembur')
                        ->modalDescription('Apakah Anda yakin ingin menolak semua pengajuan lembur yang dipilih?')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->apakah_diapprove_lembur === null) {
                                    $record->update([
                                        'apakah_diapprove_lembur' => false,
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} pengajuan lembur ditolak")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_pengajuan::lembur')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_assign_lembur')
                    ->label('Bulk Assign Lembur')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('karyawan_ids')
                            ->label('Pilih Karyawan')
                            ->options(User::where('is_active', true)->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Pilih satu atau lebih karyawan'),
                        Forms\Components\DateTimePicker::make('jam_lembur_mulai')
                            ->label('Jam Lembur Mulai')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('jam_lembur_selesai')
                            ->label('Jam Lembur Selesai')
                            ->required()
                            ->after('jam_lembur_mulai'),
                    ])
                    ->action(function (array $data) {
                        $count = 0;
                        foreach ($data['karyawan_ids'] as $karyawanId) {
                            KaryawanPekerjaan::create([
                                'karyawan_id' => $karyawanId,
                                'tipe' => TipeEnum::LEMBUR,
                                'jam_lembur_mulai' => $data['jam_lembur_mulai'],
                                'jam_lembur_selesai' => $data['jam_lembur_selesai'],
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title("{$count} pengajuan lembur berhasil dibuat")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Lembur')
                    ->visible(fn () => Auth::user()->can('create_pengajuan::lembur'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tipe'] = TipeEnum::LEMBUR;
                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePengajuanLemburs::route('/'),
        ];
    }
}
