<?php

namespace Arris\Database;

use PDO;
use PDOException;
use PDOStatement;

class LazyPDO extends PDO
{
    private ?PDO $pdo_connector = null;
    private LazyPDOConfig $config;
    private ?LazyPDOStats $stats = null;

    private string $dsn;
    private ?string $username;
    private ?string $password;
    private ?array $options;

    public function __construct(
        LazyPDOConfig $config,
        array $options = []
    ) {
        $this->config = $config;
        $this->dsn = $config->getDsn();
        $this->username = $config->getUsername();
        $this->password = $config->getPassword();
        $this->options = $config->getOptions() ?? [];
        $this->stats = new LazyPDOStats();
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

        return new LazyPDOStatement($statement, $query, $this->stats);
    }

    /**
     * @param $query
     * @param int $fetchMode
     * @param ...$fetchModeArgs
     * @return PDOStatement|false
     */
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

            $this->stats->recordQuery('query', $query, null, $startTime);

            return $result;
        } catch (PDOException $e) {
            $this->stats->recordQuery('query', $query, null, $startTime, true);
            throw $e;
        }
    }

    /**
     * @param string $statement
     * @return int|false
     */
    public function exec(string $statement): int|false
    {
        $this->ensureConnection();
        $startTime = microtime(true);

        try {
            $result = $this->pdo_connector->exec($statement);
            $this->stats->recordQuery('exec', $statement, null, $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->stats->recordQuery('exec', $statement, null, $startTime, true);
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        $this->ensureConnection();
        return $this->pdo_connector->commit();
    }

    /**
     * @return string|null
     */
    public function errorCode(): ?string
    {
        $this->ensureConnection();
        return $this->pdo_connector->errorCode() ?: null;
    }

    /**
     * @return array
     */
    public function errorInfo(): array
    {
        $this->ensureConnection();
        return $this->pdo_connector->errorInfo();
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

    public function stats():LazyPDOStats
    {
        return $this->stats;
    }
}