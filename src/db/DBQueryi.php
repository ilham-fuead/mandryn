<?php

namespace Mandryn\db;

class DBQueryi {

    private ?\PDO $pdoObj = null;
    private ?\PDOStatement $statementObj = null;
    private ?Query $preparedQueryObj = null;
    /** @var array<string, mixed> */
    private array $paramFields = [];
    public int $affectedRow = 0;

    public function __construct(string $host, string $username, string $password, string $database_name) {
        $dsn = "mysql:host={$host};dbname={$database_name};port=3306;charset=utf8";
        $this->pdoObj = new \PDO($dsn, $username, $password);
        $this->pdoObj->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function setQuery(\Mandryn\db\Query $preparedQueryObj): void {
        $this->preparedQueryObj = $preparedQueryObj;
        $this->initStatement();
        $this->bindParameters();
    }

    private function initStatement(): void {
        if ($this->pdoObj === null) {
            throw new \RuntimeException('Cannot prepare statement: PDO connection is closed.');
        }
        if ($this->preparedQueryObj === null) {
            throw new \RuntimeException('Cannot prepare statement: no query was set.');
        }

        $statement = $this->pdoObj->prepare(
            $this->preparedQueryObj->getQueryString(\Mandryn\db\constant\SqlStringType::PREPARE_STATEMENT)
        );

        if ($statement === false) {
            throw new \RuntimeException('Failed to prepare statement.');
        }

        $this->statementObj = $statement;
    }

    private function bindParameters(): void {
        if ($this->statementObj === null) {
            throw new \RuntimeException('Cannot bind parameters: no prepared statement.');
        }
        if ($this->preparedQueryObj === null) {
            throw new \RuntimeException('Cannot bind parameters: no query was set.');
        }

        $this->paramFields = [];

        if ($this->preparedQueryObj->queryType === \Mandryn\db\constant\QueryType::UPDATE) {
            foreach ($this->preparedQueryObj->updateFields as $fld) {

                /*  [ 0 - $fieldName ] , [ 1 - $value ] , [ 2 - $DataType ]  */

                $this->paramFields[$fld[0]] = $fld[1];

                if ($fld[2] === \Mandryn\db\constant\DataType::INT) {
                    $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]], \PDO::PARAM_INT);
                } else {
                    $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]]);
                }
            }
        }

        foreach ($this->preparedQueryObj->conditionFields as $fld) {

            /*  [ 0 - $fieldName ] , [ 1 - $ConditionType ] , [ 2 - $value ] , [ 3 - $DataType ] , [ 4 - $AppenderOperator ]  */

            $this->paramFields[$fld[0]] = $fld[2];

            if ($fld[3] === \Mandryn\db\constant\DataType::INT) {
                $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]], \PDO::PARAM_INT);
            } else {
                $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]]);
            }
        }
    }

    public function setFieldValue(string $fieldName, mixed $fieldValue): void {
        if (array_key_exists($fieldName, $this->paramFields)) {
            $this->paramFields[$fieldName] = $fieldValue;
        }
    }

    public function execute(): ?bool {
        try {
            if ($this->statementObj === null) {
                throw new \RuntimeException('Cannot execute: no prepared statement.');
            }
            return $this->statementObj->execute();
        } catch (\PDOException $e) {
            print $e->getMessage();
            return null;
        }
    }

    public function closeConnection(): void {
        $this->pdoObj = null;
    }

    public function __destruct() {
        $this->pdoObj = null;
    }

    /**
     * @param array<string, mixed>|null $placeholderNamesValues
     */
    public function setCustomSql(string $sqlWithPlaceholder, ?array $placeholderNamesValues = null): void {
        if ($this->pdoObj === null) {
            throw new \RuntimeException('Cannot prepare statement: PDO connection is closed.');
        }

        $statement = $this->pdoObj->prepare($sqlWithPlaceholder);

        if ($statement === false) {
            throw new \RuntimeException('Failed to prepare statement.');
        }

        $this->statementObj = $statement;

        if (is_array($placeholderNamesValues)) {
            $this->bindPlaceholderByArray($placeholderNamesValues);
        }
    }

    /**
     * @param array<string, mixed> $array
     */
    private function bindPlaceholderByArray(array $array): void {
        if ($this->statementObj === null) {
            throw new \RuntimeException('Cannot bind parameters: no prepared statement.');
        }

        $this->paramFields = [];
        foreach ($array as $placeholder => $value) {
            $this->paramFields[$placeholder] = $value;
            $this->statementObj->bindParam($placeholder, $this->paramFields[$placeholder]);
        }
    }

    /**
     * Execute the prepared statement and yield each row as a MagicObject.
     *
     * @param array<string, string> $columnMap old column name => new property name
     * @return \Generator<int, \Mandryn\db\MagicObject>
     */
    public function getCustomSqlObjects(array $columnMap = []): \Generator
    {
        if ($this->statementObj === null) {
            throw new \RuntimeException('Cannot fetch objects: no prepared statement.');
        }

        $this->statementObj->execute();
        while (($row = $this->statementObj->fetch(\PDO::FETCH_ASSOC)) !== false) {
            yield MagicObject::fromRow($row, $columnMap);
        }
    }

    public function getCustomSqlRecordset(): \Generator {
        if ($this->statementObj === null) {
            throw new \RuntimeException('Cannot fetch recordset: no prepared statement.');
        }

        $this->statementObj->execute();
        while (($row = $this->statementObj->fetch(\PDO::FETCH_ASSOC)) !== false) {
            yield $row;
        }
    }

    public function executeCustomSqlCommand(): bool {
        if ($this->statementObj === null) {
            throw new \RuntimeException('Cannot execute: no prepared statement.');
        }

        $this->affectedRow = 0;
        $status = $this->statementObj->execute();
        $this->affectedRow = $this->statementObj->rowCount();
        return $status;
    }
}
