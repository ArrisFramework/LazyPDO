<?php

namespace Arris\Database;

class LazyPDOStats
{
    private int $queryCount = 0;
    private int $preparedQueryCount = 0;
    private float $totalQueryTime = 0.0;
    private array $queries = [];

    public function recordQuery(string $type, string $query, ?array $params, float $startTime, bool $isError = false): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        if ($type === 'prepared') {
            $this->preparedQueryCount++;
        } else {
            $this->queryCount++;
        }

        $this->totalQueryTime += $duration;

        $this->queries[] = [
            'type' => $type,
            'query' => $query,
            'params' => $params,
            'time' => $duration,
            'timestamp' => $startTime,
            'is_error' => $isError
        ];
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

    /*public function getAverageQueryTime(): float
    {
        $total = $this->getTotalQueryCount();
        $total = $total > 0 ? $this->totalQueryTime / $total : 0;
        $total = round($total, 5);
        return number_format($total, 5);
    }*/

    public function getQueries(): array
    {
        return array_map(function ($row) {
            return [
                'query' => $row['query'],
                'time' => number_format($row['time'], 8),
                'timestamp' => $row['timestamp'],
                'is_error' => $row['is_error']
            ];
        }, $this->queries);
    }

    public function getLastQuery(): ?array
    {
        return end($this->queries) ?: null;
    }

    public function reset(): void
    {
        $this->queryCount = 0;
        $this->preparedQueryCount = 0;
        $this->totalQueryTime = 0.0;
        $this->queries = [];
    }
}