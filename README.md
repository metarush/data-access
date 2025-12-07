# metarush/data-access

- A generic data access layer / SQL builder for common CRUD operations.
- Can act as a layer between database and repositories/services.
- Minimal boilerplate for rapid development.
- Implicit inline binding for SQL queries.

---

## Install

Install via composer as `metarush/data-access`

## Usage

### Init library

```php
<?php

$builder = (new \MetaRush\DataAccess\Builder)
    ->setDsn('mysql:host=localhost;dbname=example') // PDO DSN
    ->setDbUser('foo')
    ->setDbPass('bar');

// or just

    ->setPdo($your_own_pdo);

$dal = $builder->build();
```

Note: `setPdo()` when used overrides `setDsn()`, `setDbUser()`, and `setDbPass()`.

### Create new row

```php
// insert 'foo' in column 'col1' and 'bar' in column 'col2'
$data = [
    'col1' => 'foo',
    'col2' => 'bar'
];
$lastInsertId = $dal->create('table', $data);
```

### Find column

```php
// find value of column 'col2' where 'col1' == 'foo'
$column = $dal->findColumn('table', ['col1' => 'foo'], 'col2');
\print_r($column); // bar
```

### Find row

```php
// find row where column 'col1' == 'foo'
$row = $dal->findOne('table', ['col1' => 'foo']);
\print_r($row);
```

### Find rows

```php
// find all rows
$rows = $dal->findAll('table');
\print_r($rows);

// find rows where column 'col1' = 'foo'
$rows = $dal->findAll('table', ['col1' => 'foo']);
\print_r($rows);

// find rows where column 'col1' = 'foo', order by col1 DESC
$rows = $dal->findAll('table', ['col1' => 'foo'], 'col1 DESC');
\print_r($rows);

// find rows where column 'col1' = 'foo', order by col2 DESC, limit 2, offset 3
$rows = $dal->findAll('table', ['col1' => 'foo'], 'col2 DESC', 2, 3);
\print_r($rows);

// find rows grouped by column 'col1'
$dal->groupBy('col1');
$rows = $dal->findAll('table');
\print_r($rows);
```

### Update rows

```php
$data = ['col1' => 'bar'];
$where = ['col2' => 'foo'];
$dal->update('table', $data, $where);
```

### Delete rows

```php
$where = ['col1' => 'foo'];
$dal->delete('table', $where);
```

### Using `$where` clause

As per `Atlas.Query` documentation, if the value of the column given is an array, the condition will be IN (). Given a null value, the condition will be IS NULL. For all other values, the condition will be =. If you pass a key without a value, that key will be used as a raw unescaped condition.

```php
$where = [
    'foo' => ['a', 'b', 'c'],
    'bar' => null,
    'baz' => 'dib',
    'zim = NOW()'
];
```

The above sample is equivalent to
`WHERE foo IN (:__1__, :__2__, :__3__) AND bar IS NULL AND baz = :__4__ AND zim = NOW()`

Other examples using other `WHERE` operators:

```php
$where = [
    'foo > 20',
    'bar <= 30',
    'baz BETWEEN 5 AND 10',
    "firstName LIKE 'test%'"
];
```

Remember, if you pass a key without a value (like these other `WHERE` operators), they will be unescaped.

### Transaction methods

```php
$dal->beginTransaction();
$dal->commit();
$dal->rollBack();
```

### Custom SQL query

You can use prepared statements with placeholders or named parameters.

#### SELECT

```php
$preparedStatement = 'SELECT * FROM table WHERE x = ? AND y = ?';
$bindParams = ['foo', 'bar'];
$fetchStyle = \PDO::FETCH_BOTH; // See https://www.php.net/manual/en/pdostatement.fetch.php for options. Default: \PDO::FETCH_BOTH
$rows = $dal->query($preparedStatement, $bindParams, $fetchStyle);
\print_r($rows);
```

#### Single INSERT

```php
$preparedStatement = "INSERT INTO table (firstName, lastName, age) VALUES (?, ?, ?)";
$bindParams = ['Mark', 'Calaway', '18'];
$numberOfAffectedRows = $dal->exec($preparedStatement, $bindParams); // returns 1
$lastInsertID = $dal->getLastInsertId();
```

#### Multiple INSERT in one statement

```php
$preparedStatement = "INSERT INTO table (firstName, lastName, age) VALUES (?, ?, ?), (?, ?, ?)";
$bindParams = ['Mark', 'Calaway', '18', 'Dwayne', 'Johnson', '17'];
$numberOfAffectedRows = $dal->exec($preparedStatement, $bindParams); // returns 2
$lastInsertID = $dal->getLastInsertId();
```

#### UPDATE

```php
$preparedStatement = "UPDATE table SET age = ? WHERE lastName = 'Doe'";
$bindParams = ['18'];
$numberOfAffectedRows = $dal->exec($preparedStatement, $bindParams);
```

#### DELETE

```php
$preparedStatement = "DELETE FROM table WHERE lastName = ?";
$bindParams = ['Doe'];
$numberOfAffectedRows = $dal->exec($preparedStatement, $bindParams);
```

### Optional config/builder methods

```php
->setStripMissingColumns(true);
```

If set to`true`,`create()`and `update()` methods will strip missing columns in their `$data`  parameter.

Useful if you want to inject an array that has keys other than database fields. This will discard array keys that is not in the `tablesDefinition` (see below).

---

```php
->setTablesDefinition(array $tablesDefinition);
```

Required when using `setStripMissingColumns(true)`.

Example parameter for `$tablesDefinition`:

```php
$tablesDefinition = [
    'UsersTable' => [ // table name
        'id', 'firstName', 'lastName' // column names
    ],
    'PostsTable' => [ // table name
        'id', 'subject', 'message' // columns names
    ]
];
```

---

```php
->setLogger(use Psr\Log\LoggerInterface $logger);
```

Set a PSR-3 compatible logger of your choice.

---

### Other service methods

```php
->setStripMissingColumns(bool);
```

Similar to the config method, you can set this per query.

```php
->getLastInsertId();
```

Get last insert id.

--

## Current adapters

- PDO (via Atlas.Query)