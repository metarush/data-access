<?php

namespace MetaRush\DataAccess;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Config
{
    private string $adapter = 'AtlasQuery';
    private string $dsn;
    private ?string $dbUser = null;
    private ?string $dbPass = null;
    private ?\PDO $pdo = null;
    private bool $stripMissingColumns = false;
    private LoggerInterface $logger;

    /**
     *
     * @var mixed[]
     */
    private array $tablesDefinition;

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setAdapter(string $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function setDsn(string $dsn): self
    {
        $this->dsn = $dsn;

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->dbUser;
    }

    public function setDbUser(?string $dbUser = null): self
    {
        $this->dbUser = $dbUser;

        return $this;
    }

    public function getDbPass(): ?string
    {
        return $this->dbPass;
    }

    public function setDbPass(?string $dbPass = null): self
    {
        $this->dbPass = $dbPass;

        return $this;
    }

    public function getStripMissingColumns(): bool
    {
        return $this->stripMissingColumns;
    }

    public function setStripMissingColumns(bool $stripMissingColumns): self
    {
        $this->stripMissingColumns = $stripMissingColumns;

        return $this;
    }

    /**
     *
     * @return mixed[]
     */
    public function getTablesDefinition(): array
    {
        return $this->tablesDefinition;
    }

    /**
     *
     * @param mixed[] $tablesDefinition
     * @return self
     */
    public function setTablesDefinition(array $tablesDefinition): self
    {
        $this->tablesDefinition = $tablesDefinition;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        $this->logger ??= new NullLogger();

        return $this->logger;
    }

    public function getPdo(): ?\PDO
    {
        return $this->pdo;
    }

    public function setPdo(\PDO $pdo): self
    {
        $this->pdo = $pdo;

        return $this;
    }
}