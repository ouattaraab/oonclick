<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppVersionResource\Pages;
use App\Models\AppVersion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Versions App';

    protected static ?string $modelLabel = 'Version App';

    protected static ?string $pluralModelLabel = 'Versions App';

    protected static ?string $navigationGroup = 'Outils';

    protected static ?int $navigationSort = 9;

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('platform')
                ->label('Plateforme')
                ->options([
                    'android' => 'Android',
                    'ios'     => 'iOS',
                ])
                ->required(),

            TextInput::make('latest_version')
                ->label('Dernière version')
                ->required()
                ->maxLength(20)
                ->placeholder('1.2.0'),

            TextInput::make('min_version')
                ->label('Version minimale')
                ->required()
                ->maxLength(20)
                ->placeholder('1.0.0'),

            Toggle::make('force_update')
                ->label('Forcer la mise à jour')
                ->default(false),

            TextInput::make('store_url')
                ->label('URL du store')
                ->url()
                ->maxLength(500)
                ->columnSpanFull(),

            Textarea::make('release_notes')
                ->label('Notes de version')
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->label('Plateforme')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'android' => 'success',
                        'ios'     => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'android' => 'Android',
                        'ios'     => 'iOS',
                        default   => $state,
                    }),

                TextColumn::make('latest_version')
                    ->label('Dernière version')
                    ->sortable(),

                TextColumn::make('min_version')
                    ->label('Version minimale')
                    ->color('gray'),

                ToggleColumn::make('force_update')
                    ->label('Forcer MAJ'),

                TextColumn::make('store_url')
                    ->label('URL Store')
                    ->limit(40)
                    ->url(fn ($record) => $record->store_url)
                    ->openUrlInNewTab()
                    ->placeholder('—'),

                TextColumn::make('release_notes')
                    ->label('Notes')
                    ->limit(50)
                    ->placeholder('—')
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('platform');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAppVersions::route('/'),
            'create' => Pages\CreateAppVersion::route('/create'),
            'edit'   => Pages\EditAppVersion::route('/{record}/edit'),
        ];
    }
}
