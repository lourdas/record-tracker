# Record Tracker
## Introduction

This is a PHP library with the purpose of storing record changes in a database. What does that mean?

Let's say you work with a database model (as in the MVC pattern, of course this is not required) and you create it, update it again and again, and finally delete it at the end of its lifecycle. Wouldn't it be nice to track its attribute changes over its lifecycle? Well, that's what Record Tracker does.

It's a simple library that connects to the database (currently only [PostgreSQL](https://www.postgresql.org/) is supported, MySQL/MariaDB support is not yet there) and stores the model attribute changes with a few metadata about this:

- Type of record change, (**C**)reate, (**U**)pdate or (**D**)elete
- Timestamp of change
- User that created the change
- The table name of the changed record
- And the primary key of the changed record

The attribute changes are stored like this:

- Column name
- Old value
- New value

See [Usage](USAGE.md) for details about how to use the library and [Changes](CHANGES.md) for the changelog. 

The library is distributed under [Mozilla Public License 2.0 (MPL-2)](https://www.mozilla.org/en-US/MPL/2.0/).

## News
2018-07-11: Initial release.
