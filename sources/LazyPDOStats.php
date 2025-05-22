<?php

namespace Arris\Database;

class LazyPDOStats implements LazyPDOStatsInterface
{
    private ?LazyPDOConfig $config;

    private int $queryCount = 0;
    private int $preparedQueryCount = 0;
    private float $totalQueryTime = 0.0;
    private array $queries = [];
    private array $slowQueries = [];

    private float $slowQueryThreshold;
    private float $initTimestamp;

    public function __construct(LazyPDOConfig $config)
    {
        $this->config = $config;
        $this->slowQueryThreshold = $config->slowQueryThreshold;
        $this->initTimestamp = microtime(true);
    }

    public function recordQuery(string $type, string $query, ?array $params, float $startTime, bool $isError = false): void
    {
        $endTime = microtime(true); // float in seconds
        $duration = $endTime - $startTime;

        if ($type === 'prepared') {
            $this->preparedQueryCount++;
        } else {
            $this->queryCount++;
        }

        $this->totalQueryTime += $duration;

        $backtrace = [];

        if ($this->config->collectBacktrace) {
            $trace = debug_backtrace(limit: 2);
            if (count($trace) > 1) {
                $trace = $trace[1];
            }

            $backtrace = [
                'file'      =>  $trace['file'] ?? __FILE__,
                'line'      =>  $trace['line'] ?? __LINE__,
                'function'  =>  $trace['function'] ?? __METHOD__
            ];

        }

        $queryData = [
            'state'     => $isError ? 'ERROR' : 'SUCCESS',
            'type'      => $type,
            'query'     => $query,
            'params'    => $params ?? [],
            'duration'  => $duration,
            'timestamp' => $endTime - $this->initTimestamp,
            'backtrace' => $backtrace
        ];
        $this->queries[] = $queryData;

        // Запись медленного запроса
        if ($duration >= $this->slowQueryThreshold) {
            $this->slowQueries[] = $queryData;
        }
    }

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

    public function getQueries(): array
    {
        return array_map(function ($row) {
            $row['duration'] = number_format($row['duration'], self::ROUND_PRECISION);
            return $row;
        }, $this->queries);
    }

    public function getSlowQueries():array
    {
        return array_map(function ($row) {
            $row['duration'] = number_format($row['duration'], self::ROUND_PRECISION);
            return $row;
        }, $this->slowQueries);
    }

    public function getLastQuery(): ?array
    {
        $row = end($this->queries) ?: null;
        if ($row && array_key_exists('duration', $row)) {
            $row['duration'] = number_format($row['duration'], self::ROUND_PRECISION);
        }
        return $row;
    }

    public function reset(): void
    {
        $this->queryCount = 0;
        $this->preparedQueryCount = 0;
        $this->totalQueryTime = 0.0;
        $this->queries = [];
        $this->slowQueries = [];
    }
}