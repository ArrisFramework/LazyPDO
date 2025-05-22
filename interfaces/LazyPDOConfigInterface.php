<?php

namespace Arris\Database;

interface LazyPDOConfigInterface
{
    public function __construct(string $driver = 'mysql', string $host = 'localhost', string $dbname = null);

    public function setDriver(?string $driver):self;
    public function setUsername(?string $username):self;
    public function setPassword(?string $password):self;
    public function setCredentials(?string $username, ?string $password): self;

    public function setDatabase(?string $database):self;
    public function setPort(?string $port): self;

    public function setCharset(?string $charset): self;
    public function setCharsetCollation(?string $charset_collation):self;

    public function option(int $option, mixed $value): self;
    public function driverOption(int $option, mixed $value): self;

    public function getDsn(): string;
    public function getUsername(): ?string;
    public function getPassword(): ?string;
    public function getOptions(): array;

    public function connect();

    public function setSlowQueryThreshold(mixed $value, bool $as_ms = true):self;
}