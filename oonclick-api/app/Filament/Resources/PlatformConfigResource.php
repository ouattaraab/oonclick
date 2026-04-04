<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformConfigResource\Pages;
use App\Models\PlatformConfig;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformConfigResource extends Resource
{
    protected static ?string $model = PlatformConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?string $modelLabel = 'Configuration';

    protected static ?string $pluralModelLabel = 'Configurations plateforme';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Outils';

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('key')
                ->label('Clé')
                ->required()
                ->maxLength(255)
                ->disabled(fn (string $operation): bool => $operation === 'edit'),

            TextInput::make('value')
                ->label('Valeur')
                ->required()
                ->maxLength(1000),

            Select::make('type')
                ->label('Type')
                ->options([
                    'string'  => 'Texte',
                    'integer' => 'Entier',
                    'boolean' => 'Booléen',
                    'json'    => 'JSON',
                ])
                ->required(),

            TextInput::make('description')
                ->label('Description')
                ->maxLength(500),

            Toggle::make('is_public')
                ->label('Visible publiquement')
                ->default(false),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Clé copiée'),

                TextColumn::make('value')
                    ->label('Valeur')
                    ->limit(50)
                    ->tooltip(fn (PlatformConfig $record): string => $record->value ?? ''),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string'  => 'gray',
                        'integer' => 'info',
                        'boolean' => 'warning',
                        'json'    => 'primary',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'string'  => 'Texte',
                        'integer' => 'Entier',
                        'boolean' => 'Booléen',
                        'json'    => 'JSON',
                        default   => $state,
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60),

                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->label('Modifier'),
            ])
            ->defaultSort('key');
    }

    // =========================================================================
    // Pages (pas de delete)
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlatformConfigs::route('/'),
            'create' => Pages\CreatePlatformConfig::route('/create'),
            'edit'   => Pages\EditPlatformConfig::route('/{record}/edit'),
        ];
    }
}
