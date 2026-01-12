<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Karyawan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Spatie\Permission\Models\Role;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\KaryawanResource\Pages;
use App\Filament\Admin\Resources\KaryawanResource\RelationManagers;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Database\Eloquent\Model;

class KaryawanResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Karyawan';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_karyawan') && Auth::user()->can('view_any_karyawan');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_karyawan');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_karyawan');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_karyawan');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_karyawan');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_karyawan');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_karyawan');
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
            'import',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nik')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('NIK'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Nama'),
                Forms\Components\TextInput::make('no_hp')
                    ->required()
                    ->maxLength(15)
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->label('No. HP'),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('alamat')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->minLength(8)
                    ->revealable()
                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Forms\Components\Select::make('mesins')
                    ->label('Mesin yang dikerjakan (Opsional)')
                    ->relationship('mesins', 'nama')
                    ->label('Mesin')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('kode')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->default(fn($record) => $record ? $record->kode : generateKode('MSN')),
                        Forms\Components\TextInput::make('nama')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->helperText(creatableState()),
                Forms\Components\Select::make('roles')
                    ->required()
                    ->relationship('roles', 'name', modifyQueryUsing: fn(Builder $query): Builder => $query->whereNotIn('name', ['super_admin']))
                    ->label('Role/Peran')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->helperText(creatableState())
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Apakah karyawan masih aktif?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::query()->withoutRole('super_admin'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn(User $record): string => $record->nik ? "(NIK:{$record->nik})" : "(NIK:-)")
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('nik', 'like', '%' . $search . '%');
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('Kontak')
                    ->description(fn(User $record): string => $record->no_hp ? "(No. HP:{$record->no_hp})" : "(No. HP:-)")
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('no_hp', 'like', '%' . $search . '%');
                    }),
                Tables\Columns\TextColumn::make('alamat')
                    ->wrap()
                    ->getStateUsing(fn(User $record): string => $record->alamat ? $record->alamat : '-'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Keaktifan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Keaktifan')
                    ->options([
                        true => 'Aktif',
                        false => 'Tidak Aktif',
                    ]),
                ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->can('update_karyawan')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete_karyawan')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_karyawan')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKaryawans::route('/'),
        ];
    }
}
