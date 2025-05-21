<?php

namespace Arris\Database;

use PDO;
use PDOException;
use PDOStatement;
use ReturnTypeWillChange;

class LazyPDOStatement extends PDOStatement
{
    private PDOStatement $realStatement;
    private string $query;
    private LazyPDO $parent;
    private ?array $params = null;

    public function __construct(PDOStatement $statement, string $query, LazyPDO $parent)
    {
        $this->realStatement = $statement;
        $this->query = $query;
        $this->parent = $parent;
    }

    public function execute(?array $params = null): bool
    {
        $this->params = $params;
        $startTime = microtime(true);

        try {
            $result = $this->realStatement->execute($params);
            $this->parent->recordPreparedQuery($this->query, $params, $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->parent->recordPreparedQuery($this->query, $params, $startTime, true);
            throw $e;
        }
    }

    public function exec(?array $params = null): bool
    {
        return $this->execute($params);
    }

    // Проксируем основные методы PDOStatement
    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return $this->realStatement->bindValue($param, $value, $type);
    }

    public function bindParam(
        int|string $param,
        mixed      &$var,
        int        $type = PDO::PARAM_STR,
        int|null   $maxLength = 0,
        mixed      $driverOptions = null
    ): bool {
        return $this->realStatement->bindParam($param, $var, $type, $maxLength, $driverOptions);
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return $this->realStatement->fetch($mode, $cursorOrientation, $cursorOffset);
    }

    #[ReturnTypeWillChange]
    public function fetchAll(
        $mode = PDO::FETCH_BOTH,
        $fetch_argument = null,
        ...$args
    )
    {
        return $this->realStatement->fetchAll($mode, ...$args);
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->realStatement->fetchColumn($column);
    }

    public function rowCount(): int
    {
        return $this->realStatement->rowCount();
    }

    public function errorCode(): ?string
    {
        return $this->realStatement->errorCode() ?: null;
    }

    public function errorInfo(): array
    {
        return $this->realStatement->errorInfo();
    }

    public function setFetchMode($mode, $className = null, ...$args):bool
    {
        return $this->realStatement->setFetchMode($mode, ...$args);
    }

    public function closeCursor(): bool
    {
        return $this->realStatement->closeCursor();
    }

    public function debugDumpParams(): ?bool
    {
        return $this->realStatement->debugDumpParams();
    }
}