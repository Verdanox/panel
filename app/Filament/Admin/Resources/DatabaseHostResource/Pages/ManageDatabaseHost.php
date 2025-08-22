<?php

namespace App\Filament\Admin\Resources\DatabaseHostResource\Pages;

use App\Filament\Admin\Resources\DatabaseHostResource;
use App\Models\DatabaseHost;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class ManageDatabaseHost extends ViewRecord
{
    protected static string $resource = DatabaseHostResource::class;

    protected static string $view = 'filament.admin.resources.database-host-resource.pages.manage-database-host';

    public ?string $selectedDatabase = null;
    public ?string $selectedTable = null;
    public array $databases = [];
    public array $tables = [];
    public array $tableData = [];
    public string $sqlQuery = '';
    public array $queryResult = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->loadDatabases();
    }

    public function getTitle(): string
    {
        return "Database Management - {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('tabler-refresh')
                ->action('loadDatabases'),
                
            Action::make('execute_query')
                ->label('Execute Query')
                ->icon('tabler-database-search')
                ->form([
                    Textarea::make('query')
                        ->label('SQL Query')
                        ->placeholder('SELECT * FROM table_name LIMIT 10;')
                        ->required()
                        ->rows(4)
                        ->helperText('Enter your SQL query. BE CAREFUL with UPDATE/DELETE queries!')
                ])
                ->action(function (array $data) {
                    $this->executeQuery($data['query']);
                })
                ->modalWidth('lg'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Database Host Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Host Name'),
                        TextEntry::make('host')
                            ->label('Host Address'),
                        TextEntry::make('port')
                            ->label('Port'),
                        TextEntry::make('username')
                            ->label('Username'),
                        TextEntry::make('databases_count')
                            ->state(fn () => count($this->databases))
                            ->label('Databases Count'),
                    ])
                    ->columns(2),
            ]);
    }

    public function loadDatabases(): void
    {
        try {
            $connection = $this->getConnection();
            
            $result = $connection->select('SHOW DATABASES');
            $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
            
            $this->databases = collect($result)
                ->pluck('Database')
                ->filter(fn ($db) => !in_array($db, $systemDatabases))
                ->map(function ($database) use ($connection) {
                    try {
                        $tableCount = $connection->select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$database]);
                        return [
                            'name' => $database,
                            'tables' => $tableCount[0]->count ?? 0,
                        ];
                    } catch (Exception) {
                        return [
                            'name' => $database,
                            'tables' => 0,
                        ];
                    }
                })
                ->toArray();

        } catch (Exception $e) {
            Notification::make()
                ->title('Connection Error')
                ->body("Could not connect to database: {$e->getMessage()}")
                ->danger()
                ->send();
                
            $this->databases = [];
        }
    }

    public function selectDatabase(string $database): void
    {
        $this->selectedDatabase = $database;
        $this->selectedTable = null;
        $this->tableData = [];
        $this->loadTables();
    }

    public function loadTables(): void
    {
        if (!$this->selectedDatabase) {
            return;
        }

        try {
            $connection = $this->getConnection($this->selectedDatabase);
            
            $result = $connection->select('SHOW TABLES');
            $tableKey = "Tables_in_{$this->selectedDatabase}";
            
            $this->tables = collect($result)
                ->map(function ($table) use ($connection, $tableKey) {
                    $tableName = $table->$tableKey;
                    try {
                        $rowCount = $connection->select("SELECT COUNT(*) as count FROM `{$tableName}`");
                        return [
                            'name' => $tableName,
                            'rows' => $rowCount[0]->count ?? 0,
                        ];
                    } catch (Exception) {
                        return [
                            'name' => $tableName,
                            'rows' => 0,
                        ];
                    }
                })
                ->toArray();

        } catch (Exception $e) {
            Notification::make()
                ->title('Error Loading Tables')
                ->body("Could not load tables: {$e->getMessage()}")
                ->danger()
                ->send();
                
            $this->tables = [];
        }
    }

    public function selectTable(string $table): void
    {
        $this->selectedTable = $table;
        $this->loadTableData();
    }

    public function loadTableData(): void
    {
        if (!$this->selectedDatabase || !$this->selectedTable) {
            return;
        }

        try {
            $connection = $this->getConnection($this->selectedDatabase);
            
            $columns = $connection->select("DESCRIBE `{$this->selectedTable}`");
            
            $data = $connection->select("SELECT * FROM `{$this->selectedTable}` LIMIT 50");
            
            $this->tableData = [
                'columns' => collect($columns)->map(fn ($col) => (array) $col)->toArray(),
                'data' => collect($data)->map(fn ($row) => (array) $row)->toArray(),
                'total_rows' => $connection->select("SELECT COUNT(*) as count FROM `{$this->selectedTable}`")[0]->count ?? 0,
            ];

        } catch (Exception $e) {
            Notification::make()
                ->title('Error Loading Table Data')
                ->body("Could not load table data: {$e->getMessage()}")
                ->danger()
                ->send();
                
            $this->tableData = [];
        }
    }

    public function executeQuery(string $query): void
    {
        if (empty(trim($query))) {
            return;
        }

        try {
            $connection = $this->getConnection($this->selectedDatabase ?? 'mysql');
            
            $upperQuery = strtoupper(trim($query));
            $dangerousKeywords = ['DROP', 'TRUNCATE', 'DELETE', 'UPDATE'];
            
            foreach ($dangerousKeywords as $keyword) {
                if (str_starts_with($upperQuery, $keyword)) {
                    Notification::make()
                        ->title('Query Blocked')
                        ->body("Dangerous operations ({$keyword}) are not allowed for security reasons.")
                        ->warning()
                        ->send();
                    return;
                }
            }
            
            $result = $connection->select($query);
            
            $this->queryResult = [
                'success' => true,
                'data' => collect($result)->map(fn ($row) => (array) $row)->toArray(),
                'count' => count($result),
                'query' => $query,
            ];

            Notification::make()
                ->title('Query Executed')
                ->body("Query executed successfully. Returned {$this->queryResult['count']} rows.")
                ->success()
                ->send();

        } catch (Exception $e) {
            $this->queryResult = [
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query,
            ];

            Notification::make()
                ->title('Query Error')
                ->body("Query failed: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    private function getConnection(?string $database = null): Connection
    {
        return $this->record->buildConnection($database ?? 'mysql');
    }
}
