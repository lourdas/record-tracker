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

