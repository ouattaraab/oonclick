<?php

namespace App\Filament\Advertiser\Resources;

use App\Filament\Advertiser\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Mes campagnes';

    protected static ?string $modelLabel = 'Campagne';

    protected static ?string $pluralModelLabel = 'Mes campagnes';

    protected static ?int $navigationSort = 1;

    // =========================================================================
    // Scope — only show the authenticated advertiser's campaigns
    // =========================================================================

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('advertiser_id', auth()->id());
    }

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([

                Section::make('Informations générales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Nom de la campagne')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('format')
                            ->label('Format publicitaire')
                            ->required()
                            ->native(false)
                            ->options([
                                'video'   => 'Vidéo (30s)',
                                'image'   => 'Image / Bannière (5s)',
                                'quiz'    => 'Quiz interactif',
                                'flash'   => 'Flash (15s)',
                                'scratch' => 'Grattage',
                            ]),

                        TextInput::make('duration_seconds')
                            ->label('Durée (secondes)')
                            ->numeric()
                            ->default(30),

                        DateTimePicker::make('starts_at')
                            ->label('Date de début')
                            ->native(false),

                        DateTimePicker::make('ends_at')
                            ->label('Date de fin')
                            ->native(false),
                    ]),

                Section::make('Contenu & Média')
                    ->columns(2)
                    ->schema([
                        TextInput::make('media_url')
                            ->label('URL du média')
                            ->url()
                            ->placeholder('https://...')
                            ->columnSpanFull(),

                        TextInput::make('thumbnail_url')
                            ->label('URL de la vignette')
                            ->url()
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Budget')
                    ->columns(3)
                    ->schema([
                        TextInput::make('budget')
                            ->label('Budget total (FCFA)')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $cpv = (float) $get('cost_per_view') ?: 100;
                                if ($cpv > 0 && $state !== null) {
                                    $set('max_views', (int) floor((float) $state / $cpv));
                                }
                            }),

                        TextInput::make('cost_per_view')
                            ->label('Coût par vue (FCFA)')
                            ->numeric()
                            ->default(100)
                            ->suffix('FCFA')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $budget = (float) $get('budget');
                                $cpv    = (float) $state ?: 100;
                                if ($cpv > 0 && $budget > 0) {
                                    $set('max_views', (int) floor($budget / $cpv));
                                }
                            }),

                        TextInput::make('max_views')
                            ->label('Vues maximum')
                            ->numeric()
                            ->readOnly()
                            ->helperText('Budget ÷ Coût par vue'),
                    ]),
            ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Campagne')
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video'   => 'info',
                        'image'   => 'success',
                        'quiz'    => 'warning',
                        'flash'   => 'danger',
                        'scratch' => 'primary',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video'   => 'Vidéo',
                        'image'   => 'Image',
                        'quiz'    => 'Quiz',
                        'flash'   => 'Flash',
                        'scratch' => 'Grattage',
                        default   => $state,
                    }),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('views_count')
                    ->label('Vues')
                    ->state(fn (Campaign $record): string => '')
                    ->formatStateUsing(
                        fn (string $state, Campaign $record): string =>
                            number_format($record->views_count ?? 0, 0, ',', ' ')
                            . ' / '
                            . number_format($record->max_views ?? 0, 0, ',', ' ')
                            . ' vues'
                    ),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'         => 'success',
                        'pending_review' => 'warning',
                        'approved'       => 'info',
                        'paused'         => 'gray',
                        'completed'      => 'gray',
                        'rejected'       => 'danger',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'         => 'Actif',
                        'pending_review' => 'En attente',
                        'approved'       => 'Approuvé',
                        'paused'         => 'En pause',
                        'completed'      => 'Terminé',
                        'rejected'       => 'Rejeté',
                        'draft'          => 'Brouillon',
                        default          => $state,
                    }),

                TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft'          => 'Brouillon',
                        'pending_review' => 'En attente',
                        'approved'       => 'Approuvé',
                        'active'         => 'Actif',
                        'paused'         => 'En pause',
                        'completed'      => 'Terminé',
                        'rejected'       => 'Rejeté',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Voir'),

                EditAction::make()
                    ->label('Modifier')
                    ->visible(
                        fn (Campaign $record): bool =>
                            in_array($record->status, ['draft', 'paused'])
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit'   => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
