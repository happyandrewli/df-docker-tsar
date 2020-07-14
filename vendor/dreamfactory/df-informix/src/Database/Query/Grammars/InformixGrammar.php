<?php

namespace DreamFactory\Core\Informix\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Builder;

class InformixGrammar extends Grammar
{
    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return str_replace('"', '""', $value);
    }

    /**
     * @inheritdoc
     */
    public function compileSelect(Builder $query)
    {
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        //'select skip 1 first 3 "col1", "col2" from "my_table" where "col1" = 1';

        $compiled = $this->compileComponents($query);
        $stmt = 'select';
        if (isset($compiled['offset'])) {
            $stmt .= ' ' . $compiled['offset'];
            unset($compiled['offset']);
        }
        if (isset($compiled['limit'])) {
            $stmt .= ' ' . $compiled['limit'];
            unset($compiled['limit']);
        }

        return $stmt . ' ' . trim($this->concatenate($compiled));
    }

    /**
     * @inheritdoc
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    /**
     * @inheritdoc
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (!is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'distinct' : '';

        return $select . $this->columnize($columns);
    }

    /**
     * @inheritdoc
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'first ' . (int)$limit;
    }

    /**
     * @inheritdoc
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return 'skip ' . (int)$offset;
    }

    /**
     * @inheritdoc
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.u';
    }
}