<?php

namespace Mandryn\db;

class Query {

    public $queryType;
    public $tableName;
    private $tableEngineType;
    private $tableStructureSQL;
    public $selectFields;
    public $insertFields;
    public $updateFields;
    public $conditionFields;
    private $sqlStringType;
    private $valueEscaper = null;
    private $allowUnscopedWrite = false;

    public function __construct($QueryType) {
        $this->queryType = $QueryType;
        $this->selectFields = [];
        $this->insertFields = [];
        $this->updateFields = [];
        $this->conditionFields = [];
    }

    /**
     * Supplies the routine used to escape string values in SQL_STRING mode.
     *
     * SQL_STRING mode interpolates values straight into the statement, so the
     * escape routine must match the connection charset. Pass the escape_string
     * method of the connection that will run the query, for example:
     *
     *     $query->setValueEscaper([$dbQuery, 'escape_string']);
     *
     * When no escaper is set, SQL_STRING mode refuses to emit a string value
     * instead of emitting an injectable one. PREPARE_STATEMENT mode does not
     * need an escaper.
     */
    public function setValueEscaper(callable $escaper) {
        $this->valueEscaper = $escaper;

        return $this;
    }

    public function hasValueEscaper() {
        return $this->valueEscaper !== null;
    }

    /**
     * Opt in to an UPDATE or DELETE without a WHERE clause. Refused by default.
     */
    public function allowUnscopedWrite($allow = true) {
        $this->allowUnscopedWrite = (bool) $allow;

        return $this;
    }

    public function setTable($tableName) {
        $this->tableName = $this->checkIdentifier($tableName, 'table name');
    }

    final public function setCreateTableEngineType($EngineType) {
        $this->tableEngineType = $EngineType;
    }

    final public function setCreateTableStructureSQL($sql) {
        $this->tableStructureSQL = $sql;
    }

    public function setSelectField($fieldName) {
        $nums = func_num_args();

        if ($nums === 1) {
            $this->selectFields[] = $fieldName;
        } elseif ($nums > 1) {
            $this->selectFields = array_merge($this->selectFields, func_get_args());
        }
    }

    public function setUpdateField($fieldName, $value, $DataType) {
        $this->updateFields[] = array($fieldName, $value, $DataType);
    }

    public function setInsertField($fieldName, $value, $DataType) {
        $this->insertFields[] = array($fieldName, $value, $DataType);
    }

    public function setConditionField($fieldName, $ConditionType, $value, $DataType, $AppenderOperator) {
        $this->conditionFields[] = array($fieldName, $ConditionType, $value, $DataType, $AppenderOperator);
    }

    public function getQueryString($SqlStringType = \Mandryn\db\constant\SqlStringType::SQL_STRING) {
        $this->sqlStringType = $SqlStringType;

        $sqlStatement = '';

        if ($this->queryType === \Mandryn\db\constant\QueryType::CREATE) {
            $sqlStatement = $this->getCreateSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::SELECT) {
            $sqlStatement = $this->getSelectSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::INSERT) {
            $sqlStatement = $this->getInsertSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::UPDATE) {
            $sqlStatement = $this->getUpdateSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::DELETE) {
            $sqlStatement = $this->getDeleteSQL();
        }

        return $sqlStatement;
    }

    private function getCreateSQL() {
        $sqlStatement = "CREATE TABLE {$this->tableName} ENGINE={$this->tableEngineType} ";

        $sqlStatement.=$this->tableStructureSQL;

        return $sqlStatement;
    }

    private function getSelectSQL() {
        if (count($this->selectFields) === 0) {
            throw new \RuntimeException('SELECT query has no fields; call setSelectField() first.');
        }

        foreach ($this->selectFields as $field) {
            $this->checkExpression($field, 'select field');
        }

        $sqlStatement = "SELECT " . implode(',', $this->selectFields) . " FROM {$this->tableName}";

        $conditionFieldsArray = $this->getConditionFieldsArray();

        if (count($conditionFieldsArray) > 0) {
            $sqlStatement.=" WHERE " . implode(' ', $conditionFieldsArray);
        }

        return $sqlStatement;
    }

    protected function getInsertSQL() {
        $sqlStatement = "INSERT INTO {$this->tableName} ";

        $insertFieldsArray = [];
        $insertValuesArray = [];

        foreach ($this->insertFields as $fld) {

            //fieldNames
            $insertFieldsArray[] = $this->checkIdentifier($fld[0], 'insert field');

            //fieldValues
            if ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::SQL_STRING) {
                $insertValuesArray[] = ($fld[2] === \Mandryn\db\constant\DataType::INT)
                    ? $this->toInteger($fld[1], $fld[0])
                    : $this->quoteString($fld[1], $fld[0]);
            } else {
                $insertValuesArray[] = ":{$fld[0]}";
            }
        }

        $sqlStatement.='(' . implode(',', $insertFieldsArray) . ')';
        $sqlStatement.=' VALUES (' . implode(',', $insertValuesArray) . ')';

        return $sqlStatement;
    }

    protected function getUpdateSQL() {
        $updateFieldsArray = [];

        foreach ($this->updateFields as $fld) {
            $fieldName = $this->checkIdentifier($fld[0], 'update field');
            if ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::SQL_STRING) {
                $updateFieldsArray[] = ($fld[2] === \Mandryn\db\constant\DataType::INT)
                    ? "{$fieldName}={$this->toInteger($fld[1], $fld[0])}"
                    : "{$fieldName}={$this->quoteString($fld[1], $fld[0])}";
            } else {
                $updateFieldsArray[] = "{$fieldName} = :{$fld[0]}";
            }
        }

        $conditionFieldsArray = $this->getConditionFieldsArray();

        $this->assertWriteIsScoped($conditionFieldsArray, 'UPDATE');

        $sqlStatement = "UPDATE {$this->tableName} ";
        $sqlStatement.="SET " . implode(',', $updateFieldsArray);

        if (count($conditionFieldsArray) > 0) {
            $sqlStatement.=" WHERE " . implode(' ', $conditionFieldsArray);
        }

        return $sqlStatement;
    }

    protected function getDeleteSQL() {
        $conditionFieldsArray = $this->getConditionFieldsArray();

        $this->assertWriteIsScoped($conditionFieldsArray, 'DELETE');

        $sqlStatement = "DELETE FROM {$this->tableName}";

        if (count($conditionFieldsArray) > 0) {
            $sqlStatement.=" WHERE " . implode(' ', $conditionFieldsArray);
        }

        return $sqlStatement;
    }

    /**
     * An UPDATE/DELETE with no WHERE clause is almost always a mistake, and a
     * silent one. Refuse it unless the caller has explicitly opted in.
     */
    private function assertWriteIsScoped(array $conditionFieldsArray, $verb) {
        if (count($conditionFieldsArray) === 0 && !$this->allowUnscopedWrite) {
            throw new \RuntimeException(
                "Refusing to build a {$verb} without a WHERE clause. Add a condition, or call "
                . 'allowUnscopedWrite(true) if an unscoped write is genuinely intended.'
            );
        }
    }

    protected function getConditionFieldsArray() {
        $conditionFieldsArray = [];

        if ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::SQL_STRING) {
            $conditionFieldsArray = $this->getSqlStringConditionFieldsArray();
        } elseif ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::PREPARE_STATEMENT) {
            $conditionFieldsArray = $this->getPreparedStatementConditionFieldsArray();
        }

        return $conditionFieldsArray;
    }

    private function getSqlStringConditionFieldsArray() {
        $conditionFieldsArray = [];

        foreach ($this->conditionFields as $fld) {
            $appender = ($fld[4] === \Mandryn\db\constant\AppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');
            $fieldName = $this->checkExpression($fld[0], 'condition field');

            if ($fld[1] === \Mandryn\db\constant\ConditionType::IS_NULL || $fld[1] === \Mandryn\db\constant\ConditionType::IS_NOT_NULL) {
                $conditionFieldsArray[] = "{$appender}{$fieldName} {$fld[1]}";
            } elseif ($fld[3] === \Mandryn\db\constant\DataType::INT) {
                $conditionFieldsArray[] = "{$appender}{$fieldName}{$fld[1]}{$this->toInteger($fld[2], $fld[0])}";
            } else {
                $conditionFieldsArray[] = "{$appender}{$fieldName}{$fld[1]}{$this->quoteString($fld[2], $fld[0])}";
            }
        }
        return $conditionFieldsArray;
    }

    private function getPreparedStatementConditionFieldsArray() {
        $conditionFieldsArray = [];
        foreach ($this->conditionFields as $fld) {
            $appender = ($fld[4] === \Mandryn\db\constant\AppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');
            $fieldName = $this->checkIdentifier($fld[0], 'condition field');

            if ($fld[1] === \Mandryn\db\constant\ConditionType::IS_NULL || $fld[1] === \Mandryn\db\constant\ConditionType::IS_NOT_NULL) {
                $conditionFieldsArray[] = "{$appender}{$fieldName} {$fld[1]}";
            } else {
                $conditionFieldsArray[] = "{$appender}{$fieldName} " . trim($fld[1]) . " :{$fld[0]}";
            }
        }
        return $conditionFieldsArray;
    }

    /**
     * Quote and escape a string value for SQL_STRING mode.
     */
    private function quoteString($value, $fieldName = '') {
        if (!is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException(
                'Expected a scalar value for field ' . ($fieldName === '' ? '(unknown)' : $fieldName)
                . ', got ' . get_debug_type($value) . '.'
            );
        }

        if ($this->valueEscaper === null) {
            throw new \RuntimeException(
                'Refusing to build SQL with an unescaped string value for field '
                . ($fieldName === '' ? '(unknown)' : $fieldName) . '. Call setValueEscaper() with the '
                . "connection's escape routine, or request SqlStringType::PREPARE_STATEMENT instead."
            );
        }

        $escaped = call_user_func($this->valueEscaper, (string) $value);

        return "'" . $escaped . "'";
    }

    /**
     * Accept integers and integer strings only. A declared INT field must never
     * be able to carry arbitrary SQL into the statement.
     */
    private function toInteger($value, $fieldName = '') {
        if (is_int($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new \InvalidArgumentException(
            'Expected an integer for field ' . ($fieldName === '' ? '(unknown)' : $fieldName)
            . ', got ' . (is_scalar($value) ? var_export($value, true) : get_debug_type($value)) . '.'
        );
    }

    /**
     * Column and table names must be plain identifiers, optionally backtick
     * quoted and optionally qualified (table.column).
     */
    private function checkIdentifier($name, $what) {
        if (!is_string($name) || preg_match('/^`?[A-Za-z_][A-Za-z0-9_]*`?(\.[A-Za-z_][A-Za-z0-9_]*$|$)/', $name) !== 1) {
            throw new \InvalidArgumentException(
                'Invalid ' . $what . ': ' . (is_string($name) ? "'{$name}'" : get_debug_type($name)) . '.'
            );
        }

        return $name;
    }

    /**
     * Select fields and condition fields may legitimately be expressions
     * (COUNT(*), DATE(created_at)), so they are not restricted to plain
     * identifiers. Statement separators, quotes, comment markers and control
     * characters are rejected instead.
     */
    private function checkExpression($name, $what) {
        if (!is_string($name) || $name === '' || preg_match('/[;\'"\x00-\x1F]|--|\/\*|\*\//', $name) === 1) {
            throw new \InvalidArgumentException(
                'Invalid ' . $what . ': ' . (is_string($name) ? "'{$name}'" : get_debug_type($name)) . '.'
            );
        }

        return $name;
    }
}
