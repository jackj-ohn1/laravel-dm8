<?php

namespace LaravelDm8\Dm8\SchemaManager;

use Illuminate\Database\Connection;
use LaravelDm8\Dm8\Dm8Connection;
use LaravelDm8\Dm8\SchemaManager\DmColumn;
use LaravelDm8\Dm8\SchemaManager\DmPlatform;

/**
 * DM8 Schema Manager
 * 
 * Provides schema introspection methods for DM8 database.
 */
class DmSchemaManager
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The schema prefix.
     *
     * @var string
     */
    protected $schema;

    /**
     * The database platform.
     *
     * @var \LaravelDm8\Dm8\SchemaManager\DmPlatform
     */
    protected $platform;

    /**
     * Create a new schema manager instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        if ($connection instanceof Dm8Connection) {
            $this->schema = $connection->getSchema();
        } else {
            $this->schema = $connection->getConfig('database');
        }
        $this->platform = new DmPlatform();
    }

    /**
     * Get the database platform.
     *
     * @return \LaravelDm8\Dm8\SchemaManager\DmPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->platform;
    }

    /**
     * List all table columns.
     *
     * @param  string  $table
     * @return array
     */
    public function listTableColumns($table)
    {
        $table = strtoupper($table);
        
        $sql = "SELECT 
                    COLUMN_NAME,
                    DATA_TYPE,
                    DATA_LENGTH,
                    DATA_PRECISION,
                    DATA_SCALE,
                    NULLABLE,
                    DATA_DEFAULT,
                    COLUMN_ID
                FROM ALL_TAB_COLUMNS
                WHERE OWNER = UPPER(?)
                    AND TABLE_NAME = UPPER(?)
                ";

        $results = $this->connection->select($sql, [$this->schema, $table]);
        return $this->processColumnResults($results);
    }

    /**
     * Process column results into structured format.
     *
     * @param  array  $results
     * @return array
     */
    protected function processColumnResults($results)
    {
        $columns = [];

        foreach ($results as $row) {
            $columnName = strtolower($row->COLUMN_NAME ?? $row->column_name);
            $dataType = strtolower($row->DATA_TYPE ?? $row->data_type);
            
            $columns[$columnName] = [
                'name' => $columnName,
                'type' => $dataType,
                'length' => $row->DATA_LENGTH ?? $row->data_length,
                'precision' => $row->DATA_PRECISION ?? $row->data_precision,
                'scale' => $row->DATA_SCALE ?? $row->data_scale,
                'nullable' => ($row->NULLABLE ?? $row->nullable) === 'Y',
                'default' => $this->parseDefaultValue($row->DATA_DEFAULT ?? $row->data_default),
            ];
        }

        return $columns;
    }

    /**
     * Get a single column information.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \LaravelDm8\Dm8\SchemaManager\DmColumn|null
     */
    public function getColumn($table, $column)
    {
        $columns = $this->listTableColumns($table);
        $columnLower = strtolower($column);
        
        if (!isset($columns[$columnLower])) {
            return null;
        }

        return new DmColumn($columns[$columnLower]);
    }

    /**
     * Parse default value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function parseDefaultValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    /**
     * List all table indexes.
     *
     * @param  string  $table
     * @return array
     */
    public function listTableIndexes($table)
    {
        $sql = "SELECT 
                i.INDEX_NAME AS index_name,
                i.TABLE_NAME AS table_name,
                i.UNIQUENESS AS uniqueness,
                c.CONSTRAINT_TYPE AS constraint_type,
                ic.COLUMN_NAME AS column_name
            FROM 
                DBA_INDEXES i
                JOIN DBA_IND_COLUMNS ic ON i.INDEX_NAME = ic.INDEX_NAME AND i.OWNER = ic.INDEX_OWNER
                LEFT JOIN DBA_CONSTRAINTS c ON i.INDEX_NAME = c.INDEX_NAME AND i.OWNER = c.OWNER
            WHERE 
                i.OWNER = UPPER(?)
                AND i.TABLE_NAME = UPPER(?)
            ";

        $indexes = $this->connection->select($sql, [$this->schema, strtoupper($table)]);

        $results = [];
        foreach ($indexes as $index) {
            $index_name = $index->INDEX_NAME ?? $index->index_name;
            if (!isset($results[$index_name])) {
                $results[$index_name] = [
                    'index_name' => $index_name,
                    'is_unique' => $index->UNIQUENESS ?? $index->uniqueness === 'UNIQUE',
                    'is_primary' => $index->CONSTRAINT_TYPE ?? $index->constraint_type === 'P',
                    'index_columns' => [],
                ];
            }
            $column_name = $index->COLUMN_NAME ?? $index->column_name;
            $results[$index_name]['index_columns'][] = $column_name;
        }
        return $results;
    }

    public function listTableNames()
    {
        $sql = "select object_name from dba_objects where object_type='TABLE' and owner=?";
        $results = $this->connection->select($sql, [$this->schema]);
        return array_map(function($row) {
            return $row->OBJECT_NAME ?? $row->object_name;
        }, $results);
    }
}
