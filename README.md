# Record Tracker
## Introduction

This is a PHP library with the purpose of storing record changes in a database. What does that mean?

Let's say you work with a database model (as in the MVC pattern, of course this is not required) and you create it, update it again and again, and finally delete it at the end of its lifecycle. Wouldn't it be nice to track its attribute changes over its lifecycle? Well, that's what Record Tracker does.

It's a simple library that connects to the database (currently only [PostgreSQL](https://www.postgresql.org/) is supported) and stores the model attribute changes and a few metadata about this:

- Type of record change, (**C**)reate, (**U**)pdate or (**D**)elete
- Timestamp of change
- User that created the change
- The table name of the changed record
- And the primary key of the changed record

The attribute changes are stored like this:

- Column name
- Old value
- New value
