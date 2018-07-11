# Usage
## Requirements
The library requires at least PHP 5.6.0. It should work with older versions (eg. 5.4) just fine, but in case you run a older version of PHP 5.6, you should seriously consider upgrading, since versions 5.6 and 7.0 are currently under support for security fixes and soon they will be unsupported. So, newer PHP versions are probably for the best. After all, running insecure PHP versions is dangerous.

### PHP modules
PDO is required, along with the corresponding PDO module for your database. `pdo_mysql` is for MySQL/MariaDB and `pdo_pgsql` is for PostgreSQL.

### Database systems
Currently the library supports the two most used open source database systems, MySQL/MariaDB and PostgreSQL. Of these, the minimum versions should be:

Database | Version
-------- | -------
MySQL | 5.7.8
MariaDB | 10.2.3
PostgreSQL | 9.4

At first sight, you probably think these are very new versions. And they are, yes. Maybe not for PostgreSQL. Version 9.4 was released on Dec. 2014, so it's approximately 3.5 years old at the time of this writing. MySQL 5.7.9 (the first GA release) was released on Oct. 2015 (less than 3 years). MariaDB 10.2.6 (the first GA release) came rather later in the game and was released on May 2017. So, the chances of running the minimum required versions for these databases range from good (PostgreSQL) to not good (MariaDB). The reason of using these rather recent versions is the use of the `JSON` data type and functions in the library.

## Installation
### Package installation
You install the library through composer with

```sh
composer require lourdas/record-tracker
```

### Database schema installation
Depending on the database used, you will have to execute either `schema-mysql.sql` for MySQL/MariaDB or `schema-postgresql.sql` for PostgreSQL.
This contains the necessary tables (`log_record` and `log_record_detail`) that will track the changes for your records.

### Class usage
#### The main `RecordTracker` instance
The class you are going to deal with is called `RecordTracker`. The package namespace is `RecordTracker`, so the main class fullname is `RecordTracker\RecordTracker`.

You instantiate the object as:

```php
$rt = new \RecordTracker\RecordTracker([
    'dbType' => 'pgsql',
    'host' => 'localhost',
    'user' => 'username',
    'password' => 'password',
    'db' => 'mydatabase',
    'port' => 5432,
    'schema' => 'myschema',
]);
```
The class constructor accepts an associative array with these keys:
* `dbType`: The database system, one of `'mysql'` or `'pgsql'`, which are for MySQL/MariaDB and PostgreSQL respectively.
* `host`: The database host name.
* `user`: The username used for the database connection.
* `password`: The password of the user for the database connection.
* `db`: The name of the database to connect.

Two optional parameters are:
* `port`: The port name where the database server listens. If this is not specified, the library will use the default port for each database system.
* `schema`: The schema name that holds the record tracker tables. This is usually used in databases where schemas are used, like PostgreSQL, Oracle, etc. If this is not specified, the default for PostgreSQL will be `public`.

Internally the class instantiates a PDO database class instance for the respective database.

#### `RecordTracker` methods
Currently, the class is pretty basic. The only supported methods are:

```php
function insertRecordLog(
    $tableName = '',
    $recId = [],
    $recType = '',
    $byUser = '',
    $oldValues = [],
    $newValues = [],
    $calcDiffArray = true
)
```
The method inserts a record log that contains all changes in a database record. It inserts a record in the `log_record` table and for each attribute change a row in `log_record_detail`.
 
The parameters are:

* `$tableName`: The database table name whose record log is saved.
* `$recId` The primary key of the row whose changes are saved. This is an array. For simple keys this could be
```['id' => 3]``` or for composite keys, this could be
```[ 'id_customer' => 3, 'id_order' => 5543 ]```.
* `$recType` The type or record change. One of (**C**)reate, (**U**)update or (**D**)elete (1 character).
* `$byUser` A string representing the user that created the record change. This could be a username (since they are usually unique) or a user identifier. This should uniquely identify the user that caused the change.
* `$oldValues` This is an key-value based array that consists of the attribute names and their old values before the record update.
* `$newValues` This is an key-value based array that consists of the attribute names and their new values after the record update.
* `$calcDiffArray` If this is true, the `RecordTracker` class is responsible for generating the diff between the `oldValues` and the `newValues` parameters. If false, the caller must provide `oldValues` and `newValues` as arrays containing the attributes that were changed and their old and new values.

In case something goes wrong (wrong parameters, database error), an exception is thrown and no record in either table is inserted in the database. The database statements are wrapped inside a transaction, so it either succeeds or fails completely.

```php
function getRecordLogDetails($tableName = '', $priKey = [])
```
The method returns an array containing all record changes for the specified `$tableName` and primary key (`$priKey`). So if you want to look up any changes for the `order` table and for the primary key `customer_id=3` and `item_id=442`, you would call the method as:

```php
$changes = getRecordLogDetails('order', ['customer_id' => 3, 'item_id' => 442']);
```
The method would return an array something like:
```php
6 => [
    'recType' => 'C'
    'byUser' => 'a user'
    'tsChange' => '2018-07-10 20:05:43'
    'attributes' => [ 
        'name' => [ 
            'oldValue' => 'Bookk',
            'newValue' => 'Book'
        ]
    ]
],
7 => [
    'recType' => 'C'
    'byUser' => 'another user'
    'tsChange' => '2018-07-10 20:09:22'
    'attributes' => [
        'publish_date' => [ 
            'oldValue' => '201-01-15'
            'newValue' => '2001-01-15'
        ]
    ]
]
```
Then you are free to display the result as you see fit.
