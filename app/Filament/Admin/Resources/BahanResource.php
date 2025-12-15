<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Bahan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\BahanResource\Pages;
use App\Filament\Admin\Resources\BahanResource\RelationManagers;

class BahanResource extends Resource
{
    protected static ?string $model = Bahan::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode Bahan')
                    ->required()
                    ->maxLength(255)
                    ->helperText(customableState())
                    ->columnSpanFull()
                    ->default(fn ($record) => $record?->kode ?? generateKode('BHN')),
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Forms\Components\Select::make('satuan_terbesar_id')
                    ->label('Satuan Terbesar')
                    ->helperText(creatableState())
                    ->required()
                    ->relationship('satuanTerbesar', 'nama')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Satuan')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
                Forms\Components\Select::make('satuan_terkecil_id')
                    ->label('Satuan Terkecil')
                    ->helperText(creatableState())
                    ->required()
                    ->relationship('satuanTerkecil', 'nama')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Satuan')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
                Forms\Components\Placeholder::make('stok')
                    ->label('Stok Tersedia')
                    ->content(fn (?Bahan $record) => $record ? number_format($record?->stok ?? 0) . ' ' . ($record?->satuanTerkecil?->nama ?? '') : '0')
                    ->helperText('Stok dihitung dari batch FIFO'),
                Forms\Components\TextInput::make('stok_minimal')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(','),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Bahan')
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('nama', 'like', '%' . $search . '%')
                            ->orWhere('kode', 'like', '%' . $search . '%');
                    })
                    ->description(fn(Bahan $record) => "(Kode:{$record->kode})"),
                Tables\Columns\TextColumn::make('satuan')
                    ->label('Satuan')
                    ->getStateUsing(function(Bahan $record) {
                        // Terbesar: ... <br> Terkecil: ...
                        return new HtmlString('Terbesar: <span class="font-bold">' . $record?->satuanTerbesar?->nama ?? '' . '</span> <br> Terkecil: <span class="font-bold">' . $record?->satuanTerkecil?->nama ?? '' . '</span>');
                    }),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Tersedia')
                    ->numeric()
                    ->sortable(false) // Cannot sort on calculated field
                    ->suffix(fn(Bahan $record) => ' ' . ($record->satuanTerkecil->nama ?? ''))
                    ->badge()
                    ->color(fn(Bahan $record) => $record->stok == 0 ? 'danger' : ($record->stok < $record->stok_minimal ? 'warning' : 'success'))
                    ->tooltip(fn(Bahan $record) => $record->stok == 0 ? 'Stok habis' : ($record->stok < $record->stok_minimal ? 'Stok kurang' : 'Stok cukup'))
                    ->getStateUsing(fn(Bahan $record) => $record->stok),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // filter status stok
                Tables\Filters\Filter::make('status_stok')
                    ->label('Status Stok')
                    ->form([
                        Forms\Components\Select::make('status_stok')
                            ->label('Status Stok')
                            ->options([
                                'habis' => 'Stok habis',
                                'kurang' => 'Stok kurang',
                                'cukup' => 'Stok cukup',
                            ])
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih Status Stok'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status_stok'] === 'habis', function (Builder $query) {
                            return $query->whereRaw('(
                                SELECT COALESCE(SUM(jumlah_tersedia), 0)
                                FROM bahan_stok_batches
                                WHERE bahan_stok_batches.bahan_id = bahans.id
                                AND jumlah_tersedia > 0
                            ) = 0');
                        })->when($data['status_stok'] === 'kurang', function (Builder $query) {
                            return $query->whereRaw('(
                                SELECT COALESCE(SUM(jumlah_tersedia), 0)
                                FROM bahan_stok_batches
                                WHERE bahan_stok_batches.bahan_id = bahans.id
                                AND jumlah_tersedia > 0
                            ) < bahans.stok_minimal');
                        })->when($data['status_stok'] === 'cukup', function (Builder $query) {
                            return $query->whereRaw('(
                                SELECT COALESCE(SUM(jumlah_tersedia), 0)
                                FROM bahan_stok_batches
                                WHERE bahan_stok_batches.bahan_id = bahans.id
                                AND jumlah_tersedia > 0
                            ) >= bahans.stok_minimal');
                        });
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('view_batches')
                    ->label('Lihat Batch')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Bahan $record) => Pages\BahanBatchPage::getUrl(['record' => $record->id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBahans::route('/'),
            'batch' => Pages\BahanBatchPage::route('/{record}/batch'),
        ];
    }
}
