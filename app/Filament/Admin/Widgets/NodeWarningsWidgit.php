<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Node;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NodeWarningsWidget extends BaseWidget
{
    protected static ?string $heading = 'Node Resource Warnings';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Node::query()->whereIn('id', auth()->user()->accessibleNodes()->pluck('id'))
                    ->where(function ($query) {
                        $query->where('has_resource_warnings', true)
                            ->orWhereRaw('EXISTS (SELECT 1 FROM nodes n WHERE n.id = nodes.id AND (
                                SELECT CASE 
                                    WHEN JSON_EXTRACT(n.statistics(), "$.cpu_percent") >= n.cpu_warning_threshold THEN 1
                                    WHEN (JSON_EXTRACT(n.statistics(), "$.memory_used") / JSON_EXTRACT(n.statistics(), "$.memory_total") * 100) >= n.memory_warning_threshold THEN 1
                                    WHEN (JSON_EXTRACT(n.statistics(), "$.disk_used") / JSON_EXTRACT(n.statistics(), "$.disk_total") * 100) >= n.disk_warning_threshold THEN 1
                                    ELSE 0
                                END
                            ) = 1)');
                    })
            )
            ->columns([
                IconColumn::make('warning_indicator')
                    ->label('')
                    ->icon('tabler-alert-triangle')
                    ->color('warning')
                    ->size('lg'),
                    
                TextColumn::make('name')
                    ->label('Node')
                    ->icon('tabler-server-2')
                    ->url(fn (Node $record) => route('filament.admin.resources.nodes.edit', ['record' => $record->id]))
                    ->weight('bold'),
                    
                TextColumn::make('fqdn')
                    ->label('Address')
                    ->icon('tabler-network'),
                    
                TextColumn::make('resource_warnings')
                    ->label('Resource Issues')
                    ->state(function (Node $record) {
                        $warnings = $record->checkResourceWarnings();
                        if (empty($warnings)) {
                            return 'No current warnings';
                        }
                        
                        return collect($warnings)
                            ->map(fn ($warning) => "{$warning['type']}: {$warning['current']}%")
                            ->join(', ');
                    })
                    ->badge()
                    ->color(function (Node $record) {
                        $warnings = $record->checkResourceWarnings();
                        if (empty($warnings)) {
                            return 'success';
                        }
                        
                        $maxSeverity = collect($warnings)->max('severity');
                        return match ($maxSeverity) {
                            'critical' => 'danger',
                            'high' => 'warning',
                            default => 'primary',
                        };
                    }),
                    
                TextColumn::make('last_resource_check')
                    ->label('Last Check')
                    ->dateTime()
                    ->since()
                    ->placeholder('Never'),
                    
                TextColumn::make('servers_count')
                    ->counts('servers')
                    ->label('Servers')
                    ->icon('tabler-brand-docker'),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('tabler-eye')
                    ->url(fn (Node $record) => route('filament.admin.resources.nodes.edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                    
                Action::make('refresh_stats')
                    ->label('Refresh')
                    ->icon('tabler-refresh')
                    ->action(function (Node $record) {
                        $record->checkResourceWarnings();
                        $this->dispatch('$refresh');
                    }),
            ])
            ->emptyStateIcon('tabler-check-circle')
            ->emptyStateHeading('No Resource Warnings')
            ->emptyStateDescription('All nodes are operating within normal resource thresholds.')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return Node::whereIn('id', auth()->user()->accessibleNodes()->pluck('id'))
            ->where('has_resource_warnings', true)
            ->exists();
    }
}
