# Database Library - Laravel Implementation

This is a Laravel-based implementation of the database utility library

## Directory Structure

```
app/Library/Db/
├── Core/
│   ├── DbInterface.php          # Interface defining all DB operations
│   ├── DbAbstract.php           # Abstract class with common implementations
│   ├── DbFactory.php            # Factory class to create DB instances
│   ├── ExpressionInterface.php  # Interface for SQL expressions
│   ├── ExpressionAbstract.php   # Abstract class for common expressions
│   ├── SQLFragmentInterface.php # Interface for SQL fragments
│   ├── SQLFragment.php          # Class for SQL fragments with parameters
│   ├── MySQL/
│   │   ├── MySQL.php            # MySQL-specific implementation
│   │   └── Expression.php       # MySQL-specific SQL expressions
│   └── Oracle/
│       ├── Oracle.php           # Oracle-specific implementation
│       └── Expression.php       # Oracle-specific SQL expressions
├── DB.php                       # Main DB class (extends MySQL)
└── DBSchema.php                 # Database schema constants
```

## Configuration

Make sure your database connections are configured in `config/database.php`:

### MySQL Configuration Example
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
],
```

### Oracle Configuration Example
```php
'oracle' => [
    'driver' => 'oracle',
    'tns' => env('DB_TNS', ''),
    'host' => env('DB_HOST', ''),
    'port' => env('DB_PORT', '1521'),
    'database' => env('DB_DATABASE', ''),
    'username' => env('DB_USERNAME', ''),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'AL32UTF8'),
    'prefix' => '',
],
```

## Usage Examples

### Basic Usage with Default Connection

```php
use App\Library\Db\DB;
use App\Library\Db\DBSchema;

// Using the default MySQL connection
$db = new DB(['connection' => 'mysql']);

// Fetch single row
$query = "SELECT * FROM users WHERE user_email = :email";
$values = [':email' => 'user@example.com'];
$user = $db->fetchRow($query, $values);

// Fetch multiple rows
$query = "SELECT * FROM users WHERE status = :status";
$values = [':status' => 'active'];
$users = $db->fetchRows($query, $values);

// Insert data
$data = [
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'user_status' => 1
];
$lastId = $db->insert(DBSchema::USERS, $data);

// Update data
$query = "UPDATE users SET user_status = :status WHERE user_id = :id";
$values = [':status' => 0, ':id' => 123];
$affectedRows = $db->update($query, $values);

// Delete data
$query = "DELETE FROM users WHERE user_id = :id";
$values = [':id' => 123];
$affectedRows = $db->delete($query, $values);
```

### Using DbFactory for Multiple Connections

```php
use App\Library\Db\Core\DbFactory;

// MySQL Connection
$factory = new DbFactory();
$mysqlDb = $factory->init(['connection' => 'mysql']);

// Oracle Connection
$oracleDb = $factory->init(['connection' => 'oracle']);

// Fetch from MySQL
$mysqlData = $mysqlDb->fetchRows("SELECT * FROM table1");

// Fetch from Oracle
$oracleData = $oracleDb->fetchRows("SELECT * FROM table2");
```

### Using SQL Expressions

```php
use App\Library\Db\DB;

$db = new DB(['connection' => 'mysql']);
$expr = $db->getExpression();

// Date expressions
$query = "SELECT id, name, " . $expr->getDate('created_at') . " as created 
          FROM users WHERE user_id = :id";
$user = $db->fetchRow($query, [':id' => 1]);

// Insert with date
$dateFragment = $expr->setDate('2025-12-25 10:30:00');
$uuidFragment = $expr->setUuid('550e8400-e29b-41d4-a716-446655440000');

// Using SQL fragments in insert
$data = [
    'user_name' => 'John',
    'created_date' => $dateFragment,
    'user_uuid' => $uuidFragment
];
$db->insert('users', $data);
```

### Pagination

```php
use App\Library\Db\DB;

$db = new DB(['connection' => 'mysql']);

$query = "SELECT * FROM users WHERE status = :status ORDER BY id";
$values = [':status' => 'active'];
$pageNumber = 1;
$recordsPerPage = 50;

$results = $db->fetchChunk($query, $values, $pageNumber, $recordsPerPage);
```

### Building WHERE Clauses

```php
use App\Library\Db\DB;

$db = new DB(['connection' => 'mysql']);

$filters = [
    'AND' => [
        ['field' => 'status', 'operator' => '=', 'value' => 1],
        'OR' => [
            ['field' => 'username', 'operator' => '%LIKE%', 'value' => 'john', 'transform' => 'lower'],
            ['field' => 'email', 'operator' => 'LIKE%', 'value' => 'admin', 'transform' => 'lower'],
        ]
    ]
];

[$whereClause, $values] = $db->buildWhereClause($filters);

$query = "SELECT * FROM users WHERE " . $whereClause;
$results = $db->fetchRows($query, $values);
```

### Query Logging

```php
use App\Library\Db\DB;

// Set log directory
DB::setQueryLogDirPath(storage_path('logs'));

// Start logging for a code chunk
DB::startQueryLog('user_operations');

$db = new DB(['connection' => 'mysql']);

// All queries executed here will be logged
$users = $db->fetchRows("SELECT * FROM users");
$db->update("UPDATE users SET status = :status WHERE id = :id", [':status' => 1, ':id' => 10]);

// End logging (will write log file)
DB::endQueryLog('user_operations');

// Log file will be created: storage/logs/db_query_log_user_operations_YYYY_MM_DD_HH_MM_SS.json
```

### Multiple Inserts (Batch Insert)

```php
use App\Library\Db\DB;

$db = new DB(['connection' => 'mysql']);

// Insert multiple rows at once
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com']
];

$lastId = $db->insert('users', $data);
```

### Oracle Specific: Using Sequences

```php
use App\Library\Db\Core\DbFactory;

$factory = new DbFactory();
$oracleDb = $factory->init(['connection' => 'oracle']);

// For Oracle, you need to specify sequence information
$table = [
    'table' => 'USERS',
    'sequence' => 'USERS_SEQ',
    'column' => 'USER_ID'
];

$data = [
    'USER_NAME' => 'John Doe',
    'USER_EMAIL' => 'john@example.com'
];

$generatedId = $oracleDb->insert($table, $data);
```
