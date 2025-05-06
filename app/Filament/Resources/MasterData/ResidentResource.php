<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\ResidentResource\Pages;
use App\Filament\Resources\MasterData\ResidentResource\RelationManagers;
use App\Filament\Resources\MasterData\ResidentResource\RelationManagers\UsersRelationManager;
use App\Models\MasterData\Resident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Str;

class ResidentResource extends Resource
{
    protected static ?string $model = Resident::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Warga';

    protected static ?string $navigationGroup = 'Data Master';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Utama')
                    ->schema([
                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Foto')
                            ->image()
                            ->directory('public/residents-photos')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->imageEditor()
                            ->columnSpanFull()
                            ->alignCenter(),


                        Forms\Components\TextInput::make('code')
                            ->label('Kode Warga')
                            ->default('WRG-' . Str::upper(Str::random(4)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Data Pribadi')
                    ->schema([
                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan'
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->maxDate(now())
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('death_date')
                            ->label('Tanggal Meninggal')
                            ->maxDate(now())
                            ->columnSpan(1),

                        Forms\Components\Select::make('religion')
                            ->label('Agama')
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Katolik' => 'Katolik',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu'
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(4),
                Forms\Components\Section::make('Kontak & Alamat')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('No. HP')
                            ->tel()
                            ->maxLength(15)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('JK')
                    ->formatStateUsing(fn(string $state): string => $state === 'Laki-laki' ? 'L' : 'P')
                    ->sortable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Tgl Lahir')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('age')
                    ->label('Usia')
                    ->state(function (Resident $record): string {
                        if ($record->death_date) {
                            return floor($record->birth_date->floatDiffInYears($record->death_date)) . ' thn (Meninggal)';
                        }
                        return floor($record->birth_date->floatDiffInYears(now())) . ' thn';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan'
                    ]),

                Tables\Filters\SelectFilter::make('religion')
                    ->options([
                        'Islam' => 'Islam',
                        'Kristen' => 'Kristen',
                        'Katolik' => 'Katolik',
                        'Hindu' => 'Hindu',
                        'Buddha' => 'Buddha',
                        'Konghucu' => 'Konghucu'
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('createUser')
                    ->label('Buat User')
                    ->icon('heroicon-o-user-plus')
                    ->action(function (Resident $record) {
                        // Logika untuk membuat user dari data warga
                        if (!$record->email) {
                            throw new \Exception('Email harus diisi untuk membuat user');
                        }

                        $user = \App\Models\User::create([
                            'name' => $record->name,
                            'email' => $record->email,
                            'password' => bcrypt('password'), // Default password
                        ]);

                        $record->user()->associate($user);
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('User berhasil dibuat')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Resident $record): bool => !$record->user && $record->email),

                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidents::route('/'),
            'create' => Pages\CreateResident::route('/create'),
            'view' => Pages\ViewResident::route('/{record}'),
            // 'edit' => Pages\EditResident::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
