<?php

namespace Arris\Database;

class LazyPDOConfig
{
    public string $driver = 'mysql';
    public string $host = 'localhost';
    private ?string $port = '3306';
    private ?string $database;

    private ?string $username = null;
    private ?string $password = null;

    private array $options = [];
    private array $driverOptions = [];

    public ?string $charset = null;
    public ?string $charset_collation = null;

    public function __construct(string $driver = 'mysql', string $host = 'localhost', string $dbname = null)
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->database = $dbname;
    }

    public function setDriver(?string $driver):self
    {
        $this->driver = $driver;
        return $this;
    }

    public function setDatabase(?string $database):self
    {
        $this->database = $database;
        return $this;
    }

    public function setPort(?string $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function setCharset(?string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function setCharsetCollation(?string $charset_collation):self
    {
        $this->charset_collation = $charset_collation;
        return $this;
    }

    public function setUsername(?string $username):self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(?string $password):self
    {
        $this->password = $password;
        return $this;
    }

    public function credentials(?string $username, ?string $password): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    public function option(int $option, mixed $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function driverOption(int $option, mixed $value): self
    {
        $this->driverOptions[$option] = $value;
        return $this;
    }

    public function getDsn(): string
    {
        $dsnParts = [
            'host=' . $this->host,
            'dbname=' . $this->database
        ];

        if ($this->port !== null) {
            $dsnParts[] = 'port=' . $this->port;
        }

        if ($this->charset !== null) {
            $dsnParts[] = 'charset=' . $this->charset;
        }

        return $this->driver . ':' . implode(';', $dsnParts);
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getOptions(): array
    {
        return $this->options + $this->driverOptions;
    }

    public function connect()
    {
        return new LazyPDO($this);
    }

}
