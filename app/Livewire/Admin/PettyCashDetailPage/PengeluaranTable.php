<?php

namespace App\Livewire\Admin\PettyCashDetailPage;

use Carbon\Carbon;
use Filament\Forms\Get;
use Livewire\Component;
use App\Models\PettyCash;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\PettyCashFlow;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Enums\PettyCashFlow\TipeEnum;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use App\Enums\PettyCashFlow\StatusApprovalEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;

class PengeluaranTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public function render()
    {
        return view('livewire.admin.petty-cash-detail-page.pengeluaran-table');
    }

    public PettyCash $pettyCash;

    public function mount(PettyCash $pettyCash): void
    {
        $this->pettyCash = $pettyCash;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PettyCashFlow::query()
                    ->where('tipe', TipeEnum::PENGELUARAN)
                    ->where('petty_cash_id', $this->pettyCash->id)
                    ->with(['user', 'approvedBy'])
                    ->latest()
            )
            ->headerActions([
                Action::make('buat_pengeluaran')
                    ->label('Pengeluaran Operasional')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Wajib memasukkan keterangan pengeluaran'),
                        FileUpload::make('bukti_transaksi')
                            ->label('Bukti Transaksi')
                            ->image()
                            ->required()
                            ->optimize('webp')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->helperText('Upload bukti transaksi (maks 2MB)'),
                    ])
                    ->action(function (array $data) {
                        $expense = PettyCashFlow::create([
                            'tipe' => TipeEnum::PENGELUARAN,
                            'petty_cash_id' => $this->pettyCash->id, // Ambil langsung dari property
                            'user_id' => Auth::id(),
                            'jumlah' => $data['jumlah'],
                            'keterangan' => $data['keterangan'],
                            'status_approval' => StatusApprovalEnum::PENDING,
                        ]);

                        $expense->addMedia(public_path('storage' . $data['bukti_transaksi']))
                            ->toMediaCollection('petty_cash_pengeluaran');

                        Notification::make()
                            ->title('Pengeluaran berhasil dibuat')
                            ->body('Menunggu approval')
                            ->success()
                            ->send();
                    }),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),
                IconColumn::make('approved_by')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn (PettyCashFlow $record) => $record->approved_by !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->infolist(fn (PettyCashFlow $record) => [
                        Section::make('Detail Pengeluaran')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Tanggal Session')
                                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y') : '-'),
                                        TextEntry::make('keterangan')
                                            ->label('Keterangan')
                                            ->columnSpanFull()
                                            ->default('-'),
                                        TextEntry::make('jumlah')
                                            ->label('Jumlah')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->color('danger'),
                                        TextEntry::make('user.name')
                                            ->label('User')
                                            ->default('-'),
                                        TextEntry::make('status_approval')
                                            ->label('Status Approval')
                                            ->formatStateUsing(fn () => $record->approved_by !== null ? 'Approved' : 'Pending')
                                            ->badge()
                                            ->color(fn () => $record->approved_by !== null ? 'success' : 'warning'),
                                        TextEntry::make('approvedBy.name')
                                            ->label('Approved By')
                                            ->default('-')
                                            ->visible(fn () => $record->approved_by !== null),
                                        TextEntry::make('approved_at')
                                            ->label('Tanggal Approved')
                                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y H:i') : '-')
                                            ->visible(fn () => $record->approved_at !== null),
                                        TextEntry::make('alasan_penolakan')
                                            ->label('Alasan Penolakan')
                                            ->columnSpanFull()
                                            ->default('-')
                                            ->color('danger')
                                            ->visible(fn () => $record->alasan_penolakan !== null),
                                        TextEntry::make('created_at')
                                            ->label('Tanggal Dibuat')
                                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),
                                    ]),
                            ])
                            ->columnSpan(1),
                        Section::make('Bukti Transaksi')
                            ->schema([
                                SpatieMediaLibraryImageEntry::make('bukti_transaksi')
                                    ->label('Bukti Transaksi')
                                    ->collection('petty_cash_pengeluaran')
                                    ->size('100%'),
                            ])
                            ->columnSpan(1),
                    ]),
                \Filament\Tables\Actions\Action::make('approve')
                    ->label('Approval')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (PettyCashFlow $record): bool => $record->approved_by === null)
                    ->form([
                        Placeholder::make('keterangan_display')
                            ->label('Keterangan dari Karyawan')
                            ->content(fn ($record) => $record && $record->keterangan
                                ? $record->keterangan
                                : 'Tidak ada keterangan'
                            )
                            ->columnSpanFull(),
                        ToggleButtons::make('action_type')
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
                        Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->placeholder('Masukkan alasan penolakan')
                            ->required(fn (Get $get) => $get('action_type') === 'reject')
                            ->visible(fn (Get $get) => $get('action_type') === 'reject')
                            ->columnSpanFull(),
                        Textarea::make('catatan_persetujuan')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->rows(3)
                            ->placeholder('Masukkan catatan persetujuan (opsional)')
                            ->visible(fn (Get $get) => $get('action_type') === 'approve')
                            ->columnSpanFull(),
                    ])
                    ->action(function (PettyCashFlow $record, array $data) {
                        if ($data['action_type'] === 'approve') {
                            $record->update([
                                'approved_by' => Auth::id(),
                                'approved_at' => now(),
                                'alasan_penolakan' => null,
                            ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Pengeluaran berhasil di-approve')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'alasan_penolakan' => $data['alasan_penolakan'],
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Pengeluaran ditolak')
                                ->warning()
                                ->send();
                        }
                    }),
                \Filament\Tables\Actions\EditAction::make()
                    ->form([
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Wajib memasukkan keterangan pengeluaran'),
                        FileUpload::make('bukti_transaksi')
                            ->label('Bukti Transaksi')
                            ->image()
                            ->required()
                            ->optimize('webp')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->helperText('Upload bukti transaksi (maks 2MB)'),
                    ])
                    ->using(function (PettyCashFlow $record, array $data) {
                        // Parse jumlah (remove comma separator dari mask)
                        $jumlahParsed = isset($data['jumlah']) ? (int) str_replace(',', '', (string) $data['jumlah']) : 0;
                        
                        $record->update([
                            'jumlah' => $jumlahParsed,
                            'keterangan' => $data['keterangan'] ?? null,
                        ]);

                        // Handle update bukti transaksi jika ada file baru
                        $record->clearMediaCollection('petty_cash_pengeluaran');
                        $record->addMedia(public_path('storage' . $data['bukti_transaksi']))
                            ->toMediaCollection('petty_cash_pengeluaran');

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Pengeluaran berhasil diupdate')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (PettyCashFlow $record): bool => $record->approved_by === null),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->visible(fn (PettyCashFlow $record): bool => $record->approved_by === null),
            ])
            ->emptyStateHeading('Tidak ada pengeluaran')
            ->emptyStateDescription('Belum ada pengeluaran operasional untuk session ini.');
    }
}
