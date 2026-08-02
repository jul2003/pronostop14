<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataModelController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

        $driver = DB::connection()->getDriverName();
        $database = DB::connection()->getDatabaseName();

        $tables = $this->tables($driver, $database);
        $columns = $this->columns($driver, $database);
        $foreignKeys = $this->foreignKeys($driver, $database);

        $tables = collect($tables)
            ->map(function (array $table) use ($columns, $foreignKeys) {
                $tableName = $table['name'];

                $table['columns'] = collect($columns)
                    ->where('table', $tableName)
                    ->values()
                    ->all();

                $table['foreign_keys'] = collect($foreignKeys)
                    ->where('table', $tableName)
                    ->values()
                    ->all();

                $table['referenced_by'] = collect($foreignKeys)
                    ->where('referenced_table', $tableName)
                    ->values()
                    ->all();

                return $table;
            })
            ->values()
            ->all();

        return view('admin.data-model.index', [
            'driver' => $driver,
            'database' => $database,
            'tables' => $tables,
            'foreignKeys' => $foreignKeys,
            'mermaid' => $this->mermaid($tables, $foreignKeys),
        ]);
    }

    private function tables(string $driver, ?string $database): array
    {
        return match ($driver) {
            'mysql', 'mariadb' => collect(DB::select(
                'select table_name as table_name
                 from information_schema.tables
                 where table_schema = ?
                 and table_type = ?
                 order by table_name',
                [$database, 'BASE TABLE']
            ))
                ->map(fn ($row) => [
                    'name' => $this->rowValue($row, ['table_name', 'TABLE_NAME']),
                ])
                ->filter(fn ($table) => filled($table['name']))
                ->values()
                ->all(),

            'pgsql' => collect(DB::select(
                "select table_name as table_name
                 from information_schema.tables
                 where table_schema = current_schema()
                 and table_type = 'BASE TABLE'
                 order by table_name"
            ))
                ->map(fn ($row) => [
                    'name' => $this->rowValue($row, ['table_name']),
                ])
                ->filter(fn ($table) => filled($table['name']))
                ->values()
                ->all(),

            'sqlite' => collect(DB::select(
                "select name
                 from sqlite_master
                 where type = 'table'
                 and name not like 'sqlite_%'
                 order by name"
            ))
                ->map(fn ($row) => [
                    'name' => $this->rowValue($row, ['name']),
                ])
                ->filter(fn ($table) => filled($table['name']))
                ->values()
                ->all(),

            default => [],
        };
    }

    private function columns(string $driver, ?string $database): array
    {
        return match ($driver) {
            'mysql', 'mariadb' => collect(DB::select(
                'select table_name as table_name,
                        column_name as column_name,
                        data_type as data_type,
                        column_type as column_type,
                        is_nullable as is_nullable,
                        column_key as column_key,
                        column_default as column_default,
                        extra as extra
                 from information_schema.columns
                 where table_schema = ?
                 order by table_name, ordinal_position',
                [$database]
            ))
                ->map(function ($row) {
                    $columnType = $this->rowValue($row, ['column_type', 'COLUMN_TYPE']);
                    $dataType = $this->rowValue($row, ['data_type', 'DATA_TYPE'], 'mixed');

                    return [
                        'table' => $this->rowValue($row, ['table_name', 'TABLE_NAME']),
                        'name' => $this->rowValue($row, ['column_name', 'COLUMN_NAME']),
                        'type' => $columnType ?: $dataType,
                        'data_type' => $dataType,
                        'nullable' => $this->rowValue($row, ['is_nullable', 'IS_NULLABLE']) === 'YES',
                        'primary' => $this->rowValue($row, ['column_key', 'COLUMN_KEY']) === 'PRI',
                        'default' => $this->rowValue($row, ['column_default', 'COLUMN_DEFAULT']),
                        'extra' => $this->rowValue($row, ['extra', 'EXTRA']),
                    ];
                })
                ->filter(fn ($column) => filled($column['table']) && filled($column['name']))
                ->values()
                ->all(),

            'pgsql' => $this->pgsqlColumns(),

            'sqlite' => $this->sqliteColumns(),

            default => [],
        };
    }

    private function pgsqlColumns(): array
    {
        $primaryKeys = collect(DB::select(
            "select kcu.table_name as table_name,
                    kcu.column_name as column_name
             from information_schema.table_constraints tc
             join information_schema.key_column_usage kcu
                on tc.constraint_name = kcu.constraint_name
                and tc.table_schema = kcu.table_schema
             where tc.constraint_type = 'PRIMARY KEY'
             and tc.table_schema = current_schema()
             order by kcu.table_name, kcu.ordinal_position"
        ))
            ->map(fn ($row) => $this->rowValue($row, ['table_name']).'.'.$this->rowValue($row, ['column_name']))
            ->values()
            ->all();

        return collect(DB::select(
            "select table_name as table_name,
                    column_name as column_name,
                    data_type as data_type,
                    udt_name as udt_name,
                    is_nullable as is_nullable,
                    column_default as column_default
             from information_schema.columns
             where table_schema = current_schema()
             order by table_name, ordinal_position"
        ))
            ->map(function ($row) use ($primaryKeys) {
                $table = $this->rowValue($row, ['table_name']);
                $column = $this->rowValue($row, ['column_name']);
                $dataType = $this->rowValue($row, ['data_type'], 'mixed');
                $udtName = $this->rowValue($row, ['udt_name']);

                return [
                    'table' => $table,
                    'name' => $column,
                    'type' => $udtName ?: $dataType,
                    'data_type' => $dataType,
                    'nullable' => $this->rowValue($row, ['is_nullable']) === 'YES',
                    'primary' => in_array($table.'.'.$column, $primaryKeys, true),
                    'default' => $this->rowValue($row, ['column_default']),
                    'extra' => null,
                ];
            })
            ->filter(fn ($column) => filled($column['table']) && filled($column['name']))
            ->values()
            ->all();
    }

    private function sqliteColumns(): array
    {
        $columns = [];

        foreach ($this->tables('sqlite', null) as $table) {
            $tableName = $table['name'];
            $pragmaRows = DB::select('pragma table_info('.$this->sqliteIdentifier($tableName).')');

            foreach ($pragmaRows as $row) {
                $columns[] = [
                    'table' => $tableName,
                    'name' => $this->rowValue($row, ['name']),
                    'type' => $this->rowValue($row, ['type'], 'mixed') ?: 'mixed',
                    'data_type' => $this->rowValue($row, ['type'], 'mixed') ?: 'mixed',
                    'nullable' => ! (bool) $this->rowValue($row, ['notnull'], false),
                    'primary' => (bool) $this->rowValue($row, ['pk'], false),
                    'default' => $this->rowValue($row, ['dflt_value']),
                    'extra' => null,
                ];
            }
        }

        return $columns;
    }

    private function foreignKeys(string $driver, ?string $database): array
    {
        return match ($driver) {
            'mysql', 'mariadb' => collect(DB::select(
                'select table_name as table_name,
                        column_name as column_name,
                        referenced_table_name as referenced_table_name,
                        referenced_column_name as referenced_column_name,
                        constraint_name as constraint_name
                 from information_schema.key_column_usage
                 where table_schema = ?
                 and referenced_table_name is not null
                 order by table_name, column_name',
                [$database]
            ))
                ->map(fn ($row) => [
                    'table' => $this->rowValue($row, ['table_name', 'TABLE_NAME']),
                    'column' => $this->rowValue($row, ['column_name', 'COLUMN_NAME']),
                    'referenced_table' => $this->rowValue($row, ['referenced_table_name', 'REFERENCED_TABLE_NAME']),
                    'referenced_column' => $this->rowValue($row, ['referenced_column_name', 'REFERENCED_COLUMN_NAME']),
                    'constraint' => $this->rowValue($row, ['constraint_name', 'CONSTRAINT_NAME']),
                ])
                ->filter(fn ($foreignKey) => filled($foreignKey['table']) && filled($foreignKey['referenced_table']))
                ->values()
                ->all(),

            'pgsql' => collect(DB::select(
                "select tc.table_name as table_name,
                        kcu.column_name as column_name,
                        ccu.table_name as referenced_table_name,
                        ccu.column_name as referenced_column_name,
                        tc.constraint_name as constraint_name
                 from information_schema.table_constraints tc
                 join information_schema.key_column_usage kcu
                    on tc.constraint_name = kcu.constraint_name
                    and tc.table_schema = kcu.table_schema
                 join information_schema.constraint_column_usage ccu
                    on ccu.constraint_name = tc.constraint_name
                    and ccu.table_schema = tc.table_schema
                 where tc.constraint_type = 'FOREIGN KEY'
                 and tc.table_schema = current_schema()
                 order by tc.table_name, kcu.column_name"
            ))
                ->map(fn ($row) => [
                    'table' => $this->rowValue($row, ['table_name']),
                    'column' => $this->rowValue($row, ['column_name']),
                    'referenced_table' => $this->rowValue($row, ['referenced_table_name']),
                    'referenced_column' => $this->rowValue($row, ['referenced_column_name']),
                    'constraint' => $this->rowValue($row, ['constraint_name']),
                ])
                ->filter(fn ($foreignKey) => filled($foreignKey['table']) && filled($foreignKey['referenced_table']))
                ->values()
                ->all(),

            'sqlite' => $this->sqliteForeignKeys(),

            default => [],
        };
    }

    private function sqliteForeignKeys(): array
    {
        $foreignKeys = [];

        foreach ($this->tables('sqlite', null) as $table) {
            $tableName = $table['name'];
            $pragmaRows = DB::select('pragma foreign_key_list('.$this->sqliteIdentifier($tableName).')');

            foreach ($pragmaRows as $row) {
                $foreignKeys[] = [
                    'table' => $tableName,
                    'column' => $this->rowValue($row, ['from']),
                    'referenced_table' => $this->rowValue($row, ['table']),
                    'referenced_column' => $this->rowValue($row, ['to']),
                    'constraint' => $tableName.'_'.$this->rowValue($row, ['from']).'_foreign',
                ];
            }
        }

        return $foreignKeys;
    }

    private function mermaid(array $tables, array $foreignKeys): string
    {
        $lines = [
            'erDiagram',
        ];

        foreach ($tables as $table) {
            $tableName = $this->mermaidIdentifier($table['name']);

            $lines[] = '    '.$tableName.' {';

            foreach ($table['columns'] as $column) {
                $type = $this->mermaidType($column['type'] ?? $column['data_type'] ?? 'mixed');
                $name = $this->mermaidIdentifier($column['name']);
                $suffixes = [];

                if ($column['primary'] ?? false) {
                    $suffixes[] = 'PK';
                }

                $isForeignKey = collect($table['foreign_keys'])
                    ->contains(fn ($foreignKey) => $foreignKey['column'] === $column['name']);

                if ($isForeignKey) {
                    $suffixes[] = 'FK';
                }

                $suffix = empty($suffixes) ? '' : ' '.implode(',', $suffixes);

                $lines[] = '        '.$type.' '.$name.$suffix;
            }

            $lines[] = '    }';
        }

        foreach ($foreignKeys as $foreignKey) {
            $parent = $this->mermaidIdentifier($foreignKey['referenced_table']);
            $child = $this->mermaidIdentifier($foreignKey['table']);
            $label = $foreignKey['column'].' → '.$foreignKey['referenced_column'];

            $lines[] = '    '.$parent.' ||--o{ '.$child.' : "'.$label.'"';
        }

        return implode("\n", $lines);
    }

    private function mermaidType(string $type): string
    {
        $type = strtolower($type);

        return match (true) {
            Str::contains($type, ['bigint', 'integer', 'int', 'tinyint', 'smallint', 'mediumint', 'serial']) => 'int',
            Str::contains($type, ['decimal', 'double', 'float', 'real', 'numeric']) => 'decimal',
            Str::contains($type, ['bool']) => 'boolean',
            Str::contains($type, ['date', 'time', 'timestamp']) => 'datetime',
            Str::contains($type, ['json']) => 'json',
            Str::contains($type, ['text']) => 'text',
            default => 'string',
        };
    }

    private function mermaidIdentifier(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $value);
    }

    private function sqliteIdentifier(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    private function rowValue(object|array $row, array $keys, mixed $default = null): mixed
    {
        $data = (array) $row;

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        $lowerData = [];

        foreach ($data as $key => $value) {
            $lowerData[strtolower((string) $key)] = $value;
        }

        foreach ($keys as $key) {
            $lowerKey = strtolower((string) $key);

            if (array_key_exists($lowerKey, $lowerData)) {
                return $lowerData[$lowerKey];
            }
        }

        return $default;
    }
}
