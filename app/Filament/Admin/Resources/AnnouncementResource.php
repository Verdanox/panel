<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\Server;
use App\Traits\Filament\CanCustomizePages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementResource extends Resource
{
    use CanCustomizePages;

    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'tabler-speakerphone';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Announcements';
    }

    public static function getModelLabel(): string
    {
        return 'Announcement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Announcements';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->where('is_active', true)->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Announcement Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('message')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->required()
                            ->options([
                                'info' => 'Information',
                                'warning' => 'Warning',
                                'maintenance' => 'Maintenance',
                                'critical' => 'Critical',
                            ])
                            ->default('info')
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Whether this announcement is currently active')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Targeting')
                    ->schema([
                        Select::make('target_servers')
                            ->label('Target Servers')
                            ->helperText('Leave empty to target all servers')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Server::pluck('name', 'id')->toArray())
                            ->columnSpanFull(),
                    ]),

                Section::make('Scheduling (Optional)')
                    ->schema([
                        DateTimePicker::make('scheduled_start')
                            ->label('Start Time')
                            ->helperText('When this announcement should become active'),

                        DateTimePicker::make('scheduled_end')
                            ->label('End Time')
                            ->helperText('When this announcement should automatically deactivate')
                            ->after('scheduled_start'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'info' => 'primary',
                        'warning' => 'warning',
                        'maintenance' => 'secondary',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'info' => 'tabler-info-circle',
                        'warning' => 'tabler-alert-triangle',
                        'maintenance' => 'tabler-tool',
                        'critical' => 'tabler-alert-octagon',
                        default => 'tabler-bell',
                    }),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('tabler-check')
                    ->falseIcon('tabler-x')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('target_servers')
                    ->label('Targets')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'All Servers';
                        }
                        return count($state) . ' Server(s)';
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable(),

                TextColumn::make('scheduled_start')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'info' => 'Information',
                        'warning' => 'Warning', 
                        'maintenance' => 'Maintenance',
                        'critical' => 'Critical',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('toggle_active')
                    ->label(fn (Announcement $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Announcement $record) => $record->is_active ? 'tabler-eye-off' : 'tabler-eye')
                    ->color(fn (Announcement $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (Announcement $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalDescription(fn (Announcement $record) => 
                        'Are you sure you want to ' . ($record->is_active ? 'deactivate' : 'activate') . ' this announcement?'
                    ),

                ViewAction::make()
                    ->hidden(fn ($record) => static::canEdit($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('tabler-speakerphone')
            ->emptyStateHeading('No announcements')
            ->emptyStateDescription('Create your first announcement to communicate with users')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
