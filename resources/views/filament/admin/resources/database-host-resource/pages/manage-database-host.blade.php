<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Database List -->
        <div class="lg:col-span-1">
            <x-filament::section>
                <x-slot name="heading">
                    Databases ({{ count($this->databases) }})
                </x-slot>
                
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($this->databases as $database)
                        <div 
                            wire:click="selectDatabase('{{ $database['name'] }}')"
                            class="p-3 rounded-lg border cursor-pointer transition-colors {{ $this->selectedDatabase === $database['name'] ? 'bg-primary-50 border-primary-200 dark:bg-primary-950 dark:border-primary-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="tabler-database" class="w-4 h-4" />
                                    <span class="font-medium">{{ $database['name'] }}</span>
                                </div>
                                <span class="text-sm text-gray-500">{{ $database['tables'] }} tables</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No databases found
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Tables & Content -->
        <div class="lg:col-span-2">
            @if($this->selectedDatabase)
                <!-- Tables List -->
                <x-filament::section class="mb-6">
                    <x-slot name="heading">
                        Tables in {{ $this->selectedDatabase }} ({{ count($this->tables) }})
                    </x-slot>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
                        @forelse($this->tables as $table)
                            <div 
                                wire:click="selectTable('{{ $table['name'] }}')"
                                class="p-3 rounded-lg border cursor-pointer transition-colors {{ $this->selectedTable === $table['name'] ? 'bg-primary-50 border-primary-200 dark:bg-primary-950 dark:border-primary-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                            >
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="tabler-table" class="w-4 h-4" />
                                    <div>
                                        <div class="font-medium">{{ $table['name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ number_format($table['rows']) }} rows</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-4 text-gray-500">
                                No tables found
                            </div>
                        @endforelse
                    </div>
                </x-filament::section>

                <!-- Table Data -->
                @if($this->selectedTable && !empty($this->tableData))
                    <x-filament::section>
                        <x-slot name="heading">
                            Table: {{ $this->selectedTable }} 
                            <span class="text-sm font-normal text-gray-500">({{ number_format($this->tableData['total_rows']) }} total rows, showing first 50)</span>
                        </x-slot>
                        
                        <!-- Table Structure -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold mb-2">Structure</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Field</th>
                                            <th class="px-3 py-2 text-left">Type</th>
                                            <th class="px-3 py-2 text-left">Null</th>
                                            <th class="px-3 py-2 text-left">Key</th>
                                            <th class="px-3 py-2 text-left">Default</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->tableData['columns'] as $column)
                                            <tr>
                                                <td class="px-3 py-2 font-mono">{{ $column['Field'] }}</td>
                                                <td class="px-3 py-2">{{ $column['Type'] }}</td>
                                                <td class="px-3 py-2">{{ $column['Null'] }}</td>
                                                <td class="px-3 py-2">{{ $column['Key'] }}</td>
                                                <td class="px-3 py-2">{{ $column['Default'] ?? 'NULL' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Table Data -->
                        @if(!empty($this->tableData['data']))
                            <div>
                                <h4 class="text-sm font-semibold mb-2">Data</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                @foreach(array_keys($this->tableData['data'][0] ?? []) as $header)
                                                    <th class="px-3 py-2 text-left font-mono">{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($this->tableData['data'] as $row)
                                                <tr>
                                                    @foreach($row as $value)
                                                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $value }}">
                                                            {{ is_null($value) ? 'NULL' : $value }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </x-filament::section>
                @endif
            @else
                <x-filament::section>
                    <div class="text-center py-12">
                        <x-filament::icon icon="tabler-database" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Select a Database</h3>
                        <p class="text-gray-500">Choose a database from the list to view its tables and data.</p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>

    <!-- Query Results -->
    @if(!empty($this->queryResult))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Query Results
            </x-slot>
            
            @if($this->queryResult['success'])
                <div class="mb-4">
                    <div class="text-sm text-gray-600 mb-2">
                        Query: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded">{{ $this->queryResult['query'] }}</code>
                    </div>
                    <div class="text-sm text-green-600">
                        ✓ {{ $this->queryResult['count'] }} rows returned
                    </div>
                </div>
                
                @if(!empty($this->queryResult['data']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    @foreach(array_keys($this->queryResult['data'][0] ?? []) as $header)
                                        <th class="px-3 py-2 text-left font-mono">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->queryResult['data'] as $row)
                                    <tr>
                                        @foreach($row as $value)
                                            <td class="px-3 py-2 max-w-xs truncate" title="{{ $value }}">
                                                {{ is_null($value) ? 'NULL' : $value }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <div class="p-4 bg-red-50 dark:bg-red-950 rounded-lg">
                    <div class="text-sm text-gray-600 mb-2">
                        Query: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded">{{ $this->queryResult['query'] }}</code>
                    </div>
                    <div class="text-sm text-red-600">
                        ✗ Error: {{ $this->queryResult['error'] }}
                    </div>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
