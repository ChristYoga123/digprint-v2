<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use App\Models\Mesin;
use App\Models\Kloter;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Enums\Kloter\StatusEnum as KloterStatusEnum;
use App\Filament\Admin\Resources\KloterResource\Pages;

class KloterResource extends Resource
{
    protected static ?string $model = Kloter::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    
    protected static ?string $navigationLabel = 'Kloter';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('kode')
                    ->label('Kode Kloter')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(fn($record) => $record ? $record->kode : generateKode('KLT')),
                Select::make('mesin_id')
                    ->label('Mesin')
                    ->options(
                        // get mesin by user has mesins
                        Mesin::query()
                            ->whereHas('karyawans', function ($query) {
                                $query->where('karyawan_id', Auth::id());
                            })
                            ->get()
                            ->mapWithKeys(function ($mesin) {
                                return [$mesin->id => '[' . $mesin->kode . '] ' . $mesin->nama];
                            })
                    )
                    ->required()
                    ->searchable(),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(KloterStatusEnum::class)
                    ->default(KloterStatusEnum::AKTIF)
                    ->required(),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode Kloter')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('mesin.nama')
                    ->label('Mesin')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('transaksiProses')
                    ->badge()
                    ->color('info')
                    ->suffix(' Proses')
                    ->getStateUsing(function (Kloter $record) {
                        return $record->transaksiProses->count();
                    }),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->default('-'),
                TextColumn::make('completedBy.name')
                    ->label('Diselesaikan Oleh')
                    ->default('-'),
                TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->getStateUsing(function (Kloter $record) {
                        return $record->completed_at ? Carbon::parse($record->completed_at)->format('d M Y H:i:s') : '-';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('mesin')
                    ->relationship('mesin', 'nama')
                    ->label('Mesin'),
                SelectFilter::make('status')
                    ->options(KloterStatusEnum::class)
                    ->label('Status'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('selesaikan')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF)
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Kloter')
                    ->modalDescription(fn (Kloter $record) => 'Tandai kloter ' . $record->kode . ' sebagai selesai?')
                    ->action(function (Kloter $record) {
                        try {
                            $record->update([
                                'status' => KloterStatusEnum::SELESAI->value,
                                'completed_by' => Auth::id(),
                                'completed_at' => now(),
                            ]);

                            $record->transaksiProses()->update([
                                'status_proses' => StatusProsesEnum::SELESAI,
                            ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Kloter berhasil diselesaikan')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyelesaikan kloter')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF),
                DeleteAction::make()
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKloters::route('/'),
        ];
    }
}
