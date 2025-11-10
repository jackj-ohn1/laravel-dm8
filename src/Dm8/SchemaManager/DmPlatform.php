<?php

namespace LaravelDm8\Dm8\SchemaManager;

/**
 * DM8 Database Platform
 * 
 * Provides Doctrine-compatible platform methods for DM8 database.
 */
class DmPlatform
{
    /**
     * Custom type mappings.
     *
     * @var array
     */
    protected $doctrineTypeMapping = [];

    /**
     * Default type mappings for DM8.
     *
     * @var array
     */
    protected $defaultTypeMappings = [];

    /**
     * Create a new platform instance.
     */
    public function __construct()
    {
        $this->doctrineTypeMapping = $this->defaultTypeMappings;
    }

    /**
     * Register a custom doctrine type mapping.
     *
     * @param  string  $dbType
     * @param  string  $doctrineType
     * @return void
     */
    public function registerDoctrineTypeMapping($dbType, $doctrineType)
    {
        $this->doctrineTypeMapping[strtolower($dbType)] = $doctrineType;
    }

    /**
     * Get doctrine type mapping for a database type.
     *
     * @param  string  $dbType
     * @return string
     */
    public function getDoctrineTypeMapping($dbType)
    {
        $dbType = strtolower($dbType);
        
        return $this->doctrineTypeMapping[$dbType] ?? 'string';
    }

    /**
     * Check if a doctrine type mapping exists.
     *
     * @param  string  $dbType
     * @return bool
     */
    public function hasDoctrineTypeMappingFor($dbType)
    {
        return isset($this->doctrineTypeMapping[strtolower($dbType)]);
    }

    /**
     * Get all doctrine type mappings.
     *
     * @return array
     */
    public function getDoctrineTypeMappings()
    {
        return $this->doctrineTypeMapping;
    }

    /**
     * Get platform name.
     *
     * @return string
     */
    public function getName()
    {
        return 'dm8';
    }

    /**
     * Get the SQL to create a table.
     * This is a placeholder for compatibility.
     *
     * @return string
     */
    public function getCreateTableSQL()
    {
        return 'CREATE TABLE';
    }

    /**
     * Check if platform supports sequences.
     *
     * @return bool
     */
    public function supportsSequences()
    {
        return true;
    }

    /**
     * Check if platform supports identity columns.
     *
     * @return bool
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * Get reserved keywords list.
     *
     * @return array
     */
    public function getReservedKeywordsList()
    {
        // Return empty array for now, can be extended
        return [];
    }
}
