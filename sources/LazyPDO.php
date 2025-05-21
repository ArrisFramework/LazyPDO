<?php

namespace Arris\Database;

use PDO;
use PDOException;
use PDOStatement;

class LazyPDO extends PDO
{
    private ?PDO $pdo_connector = null;
    private LazyPDOConfig $config;

    private string $dsn;
    private ?string $username;
    private ?string $password;
    private ?array $options;

    // Статистика
    private int $queryCount = 0;
    private int $preparedQueryCount = 0;
    private float $totalQueryTime = 0.0;
    private array $queries = [];


    public function __construct(
        LazyPDOConfig $config,
        array $options = []
    ) {
        $this->config = $config;
        $this->dsn = $config->getDsn();
        $this->username = $config->getUsername();
        $this->password = $config->getPassword();
        $this->options = $config->getOptions() ?? [];
    }

    private function initConnection(): void
    {
        if ($this->pdo_connector === null) {
            $this->pdo_connector = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );

            if ($this->config->charset) {
                $sql_collate = "SET NAMES {$this->config->charset}";

                if ($this->config->charset_collation) {
                    $sql_collate .= " COLLATE {$this->config->charset_collation}";
                }
                $this->pdo_connector->exec($sql_collate);
            }

            $this->pdo_connector->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo_connector->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
    }

    private function ensureConnection()
    {
        if (empty($this->pdo)) {
            $this->initConnection();
        }
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->ensureConnection();
        $statement = $this->pdo_connector->prepare($query, $options);

        if ($statement === false) {
            return false;
        }

        return new LazyPDOStatement($statement, $query, $this);
    }

    private function recordQuery(string $query, float $startTime, bool $isError = false): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->queryCount++;
        $this->totalQueryTime += $duration;

        $this->queries[] = [
            'type' => 'query',
            'query' => $query,
            'params' => null,
            'time' => $duration,
            'timestamp' => $startTime,
            'is_error' => $isError
        ];
    }

    public function recordPreparedQuery(string $query, ?array $params, float $startTime, bool $isError = false): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->preparedQueryCount++;
        $this->totalQueryTime += $duration;

        $this->queries[] = [
            'type' => 'prepared',
            'query' => $query,
            'params' => $params,
            'time' => $duration,
            'timestamp' => $startTime,
            'is_error' => $isError
        ];
    }

    // Методы для получения статистики

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getPreparedQueryCount(): int
    {
        return $this->preparedQueryCount;
    }

    public function getTotalQueryCount(): int
    {
        return $this->queryCount + $this->preparedQueryCount;
    }

    public function getTotalQueryTime(): float
    {
        return $this->totalQueryTime;
    }

    public function getAverageQueryTime(): float
    {
        $total = $this->getTotalQueryCount();
        return $total > 0 ? $this->totalQueryTime / $total : 0;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getLastQuery(): ?array
    {
        return $this->queries[count($this->queries) - 1] ?? null;
    }

    // Остальные методы PDO (аналогично предыдущей реализации)
    public function query($query, $fetchMode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetchModeArgs): PDOStatement|false
    {
        $this->ensureConnection();

        $startTime = microtime(true);

        try {
            if (func_num_args() === 1) {
                $result = $this->pdo_connector->query($query);
            } else {
                $result = $this->pdo_connector->query($query, $fetchMode, ...$fetchModeArgs);
            }

            $this->recordQuery($query, $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->recordQuery($query, $startTime, true);
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->beginTransaction();
    }

    public function commit(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->commit();
    }

    public function errorCode(): ?string
    {
        $this->ensureConnection();
        return $this->pdo_connector->errorCode() ?: null;
    }

    public function errorInfo(): array
    {
        $this->ensureConnection();
        return $this->pdo_connector->errorInfo();
    }

    public function exec(string $statement): int|false
    {
        $this->ensureConnection();
        $startTime = microtime(true);

        try {
            $result = $this->pdo_connector->exec($statement);
            $this->recordQuery($statement, $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->recordQuery($statement, $startTime, true);
            throw $e;
        }
    }

    public function getAttribute(int $attribute): mixed
    {
        $this->ensureConnection();
        return $this->pdo_connector->getAttribute($attribute);
    }

    public function inTransaction(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->inTransaction();
    }

    public function lastInsertId(?string $name = null): string|false
    {
        $this->ensureConnection();
        return $this->pdo_connector->lastInsertId($name);
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false
    {
        $this->ensureConnection();
        return $this->pdo_connector->quote($string, $type);
    }

    public function rollBack(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->rollBack();
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->setAttribute($attribute, $value);
    }
}