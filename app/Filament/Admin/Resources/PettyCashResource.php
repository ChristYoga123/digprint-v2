<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\PettyCash;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Enums\PettyCash\StatusEnum;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\PettyCashResource\Pages;
use App\Filament\Admin\Resources\PettyCashResource\RelationManagers;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class PettyCashResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = PettyCash::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Petty Cash';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_petty::cash') && Auth::user()->can('view_any_petty::cash');
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
            'approve_buka',
            'approve_tutup',
            'close_store'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Buka Toko')
                    ->description('Input uang fisik dari atasan saat buka toko')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('uang_buka')
                            ->label('Uang Buka Toko')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->placeholder(fn ($record) => $record && $record->alasan_penolakan_buka 
                                ? 'Ditolak: ' . \Illuminate\Support\Str::limit($record->alasan_penolakan_buka, 50) 
                                : null
                            )
                            ->helperText(fn ($record) => $record && $record->alasan_penolakan_buka 
                                ? new \Illuminate\Support\HtmlString('<span style="color: red;">Alasan penolakan: ' . $record->alasan_penolakan_buka . '</span>')
                                : null
                            )
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('keterangan_buka')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['userBuka', 'userTutup', 'approvedByBuka', 'approvedByTutup']))
            ->defaultSort('tanggal', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('uang_buka')
                    ->label('Uang Buka')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uang_tutup')
                    ->label('Uang Tutup')
                    ->money('IDR')
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('link_bukti_transfer')
                    ->label('Bukti Transfer')
                    ->url(fn ($record) => $record->link_bukti_transfer, shouldOpenInNewTab: true)
                    ->icon('heroicon-o-link')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge(StatusEnum::class)
                    ->sortable(),
                Tables\Columns\TextColumn::make('userBuka.name')
                    ->label('User Buka')
                    ->searchable(),
                Tables\Columns\TextColumn::make('userTutup.name')
                    ->label('User Tutup')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('approvedByBuka.name')
                    ->label('Approved Buka')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('approvedByTutup.name')
                    ->label('Approved Tutup')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('tanggal')
                    ->label('Tanggal'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusEnum::class),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('approve_buka')
                    ->label('Approval')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->color('info')
                    ->visible(fn (PettyCash $record): bool => 
                        $record->status === StatusEnum::BUKA && 
                        $record->approved_by_buka === null &&
                        Auth::user()->can('approve_buka_petty::cash')
                    )
                    ->form([
                        Forms\Components\Placeholder::make('keterangan_buka_display')
                            ->label('Keterangan dari Karyawan')
                            ->content(fn ($record) => $record && $record->keterangan_buka 
                                ? $record->keterangan_buka
                                : 'Tidak ada keterangan'
                            )
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('action_type')
                            ->label('Aksi')
                            ->options([
                                'approve' => 'Setujui',
                                'reject' => 'Tolak',
                            ])
                            ->colors([
                                'approve' => 'success',
                                'reject' => 'danger',
                            ])
                            ->required()
                            ->grouped()
                            ->live()
                            ->default('approve')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('alasan_penolakan_buka')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->placeholder('Masukkan alasan penolakan buka toko')
                            ->required(fn (Forms\Get $get) => $get('action_type') === 'reject')
                            ->visible(fn (Forms\Get $get) => $get('action_type') === 'reject')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('catatan_persetujuan')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->rows(3)
                            ->placeholder('Masukkan catatan persetujuan (opsional)')
                            ->visible(fn (Forms\Get $get) => $get('action_type') === 'approve')
                            ->columnSpanFull(),
                    ])
                    ->action(function (PettyCash $record, array $data) {
                        if ($data['action_type'] === 'approve') {
                            $record->update([
                                'approved_by_buka' => Auth::id(),
                                'approved_at_buka' => now(),
                                'alasan_penolakan_buka' => null, // Clear alasan penolakan jika approve
                                'catatan_persetujuan_buka' => $data['catatan_persetujuan'] ?? null,
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Buka toko berhasil di-approve' . (!empty($data['catatan_persetujuan']) ? ' dengan catatan' : ''))
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'alasan_penolakan_buka' => $data['alasan_penolakan_buka'],
                                'approved_by_buka' => null,
                                'approved_at_buka' => null,
                                'catatan_persetujuan_buka' => null, // Clear catatan jika reject
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Buka toko ditolak')
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('approve_tutup')
                    ->label('Approval')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (PettyCash $record): bool => 
                        $record->status === StatusEnum::TUTUP && 
                        $record->approved_by_tutup === null &&
                        Auth::user()->can('approve_tutup_petty::cash')
                    )
                    ->form([
                        Forms\Components\Placeholder::make('keterangan_tutup_display')
                            ->label('Keterangan Tutup Toko dari Karyawan')
                            ->content(fn ($record) => $record && $record->keterangan_tutup 
                                ? $record->keterangan_tutup
                                : 'Tidak ada keterangan'
                            )
                            ->visible(fn ($record) => $record && $record->keterangan_tutup !== null)
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('action_type')
                            ->label('Aksi')
                            ->options([
                                'approve' => 'Setujui',
                                'reject' => 'Tolak',
                            ])
                            ->colors([
                                'approve' => 'success',
                                'reject' => 'danger',
                            ])
                            ->required()
                            ->grouped()
                            ->live()
                            ->default('approve')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('alasan_penolakan_tutup')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->placeholder('Masukkan alasan penolakan tutup toko')
                            ->required(fn (Forms\Get $get) => $get('action_type') === 'reject')
                            ->visible(fn (Forms\Get $get) => $get('action_type') === 'reject')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('catatan_persetujuan')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->rows(3)
                            ->placeholder('Masukkan catatan persetujuan (opsional)')
                            ->visible(fn (Forms\Get $get) => $get('action_type') === 'approve')
                            ->columnSpanFull(),
                    ])
                    ->action(function (PettyCash $record, array $data) {
                        if ($data['action_type'] === 'approve') {
                            $record->update([
                                'approved_by_tutup' => Auth::id(),
                                'approved_at_tutup' => now(),
                                'alasan_penolakan_tutup' => null, // Clear alasan penolakan jika approve
                                'catatan_persetujuan_tutup' => $data['catatan_persetujuan'] ?? null,
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Tutup toko berhasil di-approve' . (!empty($data['catatan_persetujuan']) ? ' dengan catatan' : ''))
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'alasan_penolakan_tutup' => $data['alasan_penolakan_tutup'],
                                'approved_by_tutup' => null,
                                'approved_at_tutup' => null,
                                'catatan_persetujuan_tutup' => null, // Clear catatan jika reject
                                'status' => StatusEnum::BUKA->value, // Kembalikan status ke BUKA jika ditolak
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Tutup toko ditolak')
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('tutup_toko')
                    ->label('Tutup Toko')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (PettyCash $record): bool => 
                        $record->status === StatusEnum::BUKA &&
                        $record->approved_by_buka !== null && // Hanya muncul jika sudah di-approve
                        Auth::user()->can('close_store_petty::cash')
                    )
                    ->form([
                        Forms\Components\TextInput::make('uang_tutup')
                            ->label('Total Uang Saat Tutup Toko')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->placeholder(fn ($record) => $record && $record->alasan_penolakan_tutup 
                                ? 'Alasan penolakan sebelumnya: ' . \Illuminate\Support\Str::limit($record->alasan_penolakan_tutup, 50)
                                : null
                            )
                            ->helperText(fn ($record) => $record && $record->alasan_penolakan_tutup 
                                ? new \Illuminate\Support\HtmlString('<span style="color: red;">Alasan penolakan sebelumnya: ' . $record->alasan_penolakan_tutup . '</span>')
                                : 'Total uang fisik saat tutup toko'
                            )
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('link_bukti_transfer')
                            ->label('Link Bukti Transfer / Drive Collection')
                            ->url()
                            ->placeholder('https://drive.google.com/...')
                            ->helperText('Opsional. Masukkan link Google Drive atau folder bukti transfer jika ada pembayaran via transfer')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('keterangan_tutup')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (PettyCash $record, array $data) {
                        if (!$data['uang_tutup'] || $data['uang_tutup'] <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Uang tutup harus lebih dari 0.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => StatusEnum::TUTUP->value,
                            'user_id_tutup' => Auth::id(),
                            'uang_tutup' => $data['uang_tutup'],
                            'link_bukti_transfer' => $data['link_bukti_transfer'] ?? null,
                            'keterangan_tutup' => $data['keterangan_tutup'] ?? null,
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->body('Toko berhasil ditutup')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('view_transactions')
                    ->label('Lihat Transaksi')
                    ->icon('heroicon-o-eye')
                    ->url(fn(PettyCash $record) => Pages\PettyCashDetailPage::getUrl(['record' => $record]))
                    ->visible(fn () => Auth::user()->can('view_petty::cash')),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PettyCash $record): bool => 
                        $record->status === StatusEnum::BUKA &&
                        $record->approved_by_buka === null && // Hanya bisa edit jika belum di-approve
                        Auth::user()->can('update_petty::cash')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PettyCash $record): bool => 
                        $record->status === StatusEnum::BUKA && 
                        $record->approved_by_buka === null &&
                        Auth::user()->can('delete_petty::cash')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_petty::cash')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePettyCashes::route('/'),
            'detail' => Pages\PettyCashDetailPage::route('/{record}/detail'),
        ];
    }
}
