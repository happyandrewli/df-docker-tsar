<?php

namespace DreamFactory\Core\Informix\Database;

use DreamFactory\Core\Informix\Database\Query\Processors\InformixProcessor;
use DreamFactory\Core\Informix\Database\Query\Grammars\InformixGrammar as QueryGrammar;
use DreamFactory\Core\Informix\Database\Schema\Grammars\InformixGrammar as SchemaGrammar;
use Illuminate\Database\Connection;
use PDO;

class InformixConnection extends Connection
{
    /**
     * The name of the default schema.
     *
     * @var string
     */
    protected $defaultSchema;

    /**
     * The name of the current schema in use.
     *
     * @var string
     */
    protected $currentSchema;

    public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        if (isset($config['schema'])) {
            $this->currentSchema = $this->defaultSchema = strtoupper($config['schema']);
        }
    }

    /**
     * Get the name of the default schema.
     *
     * @return string
     */
    public function getDefaultSchema()
    {
        return $this->defaultSchema;
    }

    /**
     * Reset to default the current schema.
     *
     * @return string
     */
    public function resetCurrentSchema()
    {
        $this->setCurrentSchema($this->getDefaultSchema());
    }

    /**
     * Set the name of the current schema.
     *
     * @param $schema
     *
     * @return string
     */
    public function setCurrentSchema($schema)
    {
        //$this->currentSchema = $schema;
        $this->statement('SET SCHEMA ?', [strtoupper($schema)]);
    }

    /**
     * @return QueryGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Default grammar for specified Schema
     *
     * @return SchemaGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return InformixProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new InformixProcessor;
    }
}
