<?php

namespace MetaRush\DataAccess\Adapters;

use MetaRush\DataAccess\Config;
use MetaRush\DataAccess\Exception;
use Atlas\Query\Insert;
use Atlas\Query\Select;
use Atlas\Query\Update;
use Atlas\Query\Delete;

class AtlasQuery implements AdapterInterface
{
    private Config $cfg;
    private \PDO $pdo;
    private ?string $groupByColumn = null;

    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;

        try {
            $this->pdo = new \PDO($cfg->getDsn(), $cfg->getDbUser(), $cfg->getDbPass());
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $ex) { // catch to suppress potential password leak from PDO strack trace
            throw new \PDOException($ex->getMessage(), $ex->getCode()); // note: using "throw $ex" is glitchy, better to use "throw new"
        }
    }

    /**
     * @inheritDoc
     */
    public function create(string $table, array $data): int
    {
        $start = \microtime(true);

        if ($this->cfg->getStripMissingColumns())
            $data = $this->getStrippedMissingColumns($table, $data);

        /** @var Insert $insert */
        // we don't use method chaining for phpstan
        $insert = Insert::new($this->pdo);
        $insert->into($table);
        $insert->columns($data);
        $insert->perform();

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($insert->getStatement(), $insert->getBindValues(), $duration);

        // ------------------------------------------------

        /** @var int $lastId */
        $lastId = $insert->getLastInsertId();

        return $lastId;
    }

    public function findColumn(string $table, array $where, string $column): ?string
    {
        $start = \microtime(true);

        /** @var Select $select */
        // we don't use method chaining for phpstan

        $select = Select::new($this->pdo);

        $select->columns('*');
        $select->from($table);
        $select->whereEquals($where);
        $row = $select->fetchOne();

        $columnVal = $row ? $row[$column] : null;

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($select->getStatement(), $select->getBindValues(), $duration);

        // ------------------------------------------------

        /** @var string|null $columnVal */
        return $columnVal;
    }

    /**
     * @inheritDoc
     */
    public function findOne(string $table, array $where): ?array
    {
        $start = \microtime(true);

        /** @var Select $select */
        // we don't use method chaining for phpstan
        $select = Select::new($this->pdo);

        $select->columns('*');
        $select->from($table);
        $select->whereEquals($where);
        $res = $select->fetchOne();

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($select->getStatement(), $select->getBindValues(), $duration);

        // ------------------------------------------------

        /** @var mixed[]|null $res */
        return $res;
    }

    /**
     * @inheritDoc
     */
    public function findAll(string $table, ?array $where = null, ?string $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $start = \microtime(true);

        /** @var Select $select */
        // we don't use method chaining for phpstan
        $select = Select::new($this->pdo);

        $where = $where ?? [];

        $select->columns('*');
        $select->from($table);
        $select->whereEquals($where);

        if ($this->groupByColumn) {
            $select->groupBy($this->groupByColumn);
            $this->groupByColumn = null;
        }

        if ($orderBy)
            $select->orderBy($orderBy);

        if ($limit)
            $select->limit($limit);

        if ($offset)
            $select->offset($offset);

        $res = $select->fetchAll();

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($select->getStatement(), $select->getBindValues(), $duration);

        // ------------------------------------------------

        return $res;
    }

    /**
     * @inheritDoc
     */
    public function update(string $table, array $data, ?array $where = null): void
    {
        $start = \microtime(true);

        if ($this->cfg->getStripMissingColumns())
            $data = $this->getStrippedMissingColumns($table, $data);

        /** @var Update $update */
        // we don't use method chaining for phpstan
        $update = Update::new($this->pdo);

        $where = $where ?? [];

        $update->table($table);
        $update->columns($data);
        $update->whereEquals($where);
        $update->perform();

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($update->getStatement(), $update->getBindValues(), $duration);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $table, ?array $where = null): void
    {
        $start = \microtime(true);

        /** @var Delete $delete */
        // we don't use method chaining for phpstan
        $delete = Delete::new($this->pdo);

        $where = $where ?? [];

        $delete->from($table);
        $delete->whereEquals($where);
        $delete->perform();

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($delete->getStatement(), $delete->getBindValues(), $duration);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Strip missing columns in $data if they don't exist in Config::$tableDefinition
     *
     * @param string $table
     * @param mixed[] $data
     * @return mixed[]
     * @throws \MetaRush\DataAccess\Exception
     */
    protected function getStrippedMissingColumns(string $table, array $data): array
    {
        $tablesDefinition = $this->cfg->getTablesDefinition();

        if (!isset($tablesDefinition[$table]))
            throw new Exception('Table "' . $table . '" is not defined in your tables definition');

        foreach ($data as $column => $v)
            if (!\in_array($column, (array) $tablesDefinition[$table]))
                unset($data[$column]);

        return $data;
    }

    /**
     * setStripMissingColumns to override config value
     *
     * @param bool $flag
     * @return void
     */
    public function setStripMissingColumns(bool $flag): void
    {
        $this->cfg->setStripMissingColumns($flag);
    }

    /**
     * @inheritDoc
     */
    public function groupBy(string $column): void
    {
        $this->groupByColumn = $column;
    }

    /**
     * @inheritDoc
     */
    public function query(string $preparedStatement, ?array $bindParams = null, ?int $fetchStyle = null): array
    {
        $start = \microtime(true);

        $stmt = $this->pdo->prepare($preparedStatement);

        $stmt->execute($bindParams);

        $fetchStyle = $fetchStyle ? $fetchStyle : \PDO::FETCH_BOTH;

        $duration = (\microtime(true) - $start) * 1000;

        $res = (array) $stmt->fetchAll($fetchStyle);

        // ------------------------------------------------

        $this->log($preparedStatement, $bindParams, $duration);

        // ------------------------------------------------

        return $res;
    }

    /**
     * @inheritDoc
     */
    public function exec(string $preparedStatement, ?array $bindParams = null): int
    {
        $start = \microtime(true);

        $stmt = $this->pdo->prepare($preparedStatement);

        $stmt->execute($bindParams);

        $duration = (\microtime(true) - $start) * 1000;

        // ------------------------------------------------

        $this->log($preparedStatement, $bindParams, $duration);

        // ------------------------------------------------

        return $stmt->rowCount();
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     *
     * @param string $sql
     * @param mixed[]|null $params
     * @param float|null $duration
     * @return void
     */
    protected function log(string $sql, ?array $params = null, ?float $duration = null): void
    {
        $logger = $this->cfg->getLogger();

        $logger->debug('[MRDA-AQ]', [
            'sql'      => $sql,
            'params'   => $params,
            'duration' => $duration,
        ]);
    }
}