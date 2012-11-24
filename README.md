SessionStorageHandlerChainBundle
================================

Symfony2 Bundle that provides a session storage chain handler.

[![Build Status](https://secure.travis-ci.org/hautelook/SessionStorageHandlerChainBundle.png?branch=master)](https://travis-ci.org/hautelook/SessionStorageHandlerChainBundle)

## Introduction

This bundle provides a chain around the session storage handlers that will behave differently whether it's a read or a write-like
function. What that means is that with a _read_, the _first_ storage handler in the list that returns a session for a given session ID
wins. Other readers are not queried. With a _write-like_ action, _every_ storage handler will be called. The following functions of the storage handlers are considered write-like: `write()`, `destroy()`, and `gc()`.

Contributions are welcome.

## Installation

Simply run assuming you have installed composer.phar or composer binary (or add to your `composer.json` and run composer install:

```bash
$ composer require hautelook/session-storage-handler-chain
```

Now add the Bundle to your Kernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Hautelook\HautelookSessionStorageChainBundle(),
        // ...
    );
}
```

## Configuration

To configure the bundle, edit your `config.yml`, or `config_{environment}.yml`:

```yml
# Session Chain
hautelook_session_storage_chain:
    reader:
        - reader1
        - reader2
        ...
    writer:
        - writer1
        - writer2
        ...
```

### Example use case and configuration:

You want to use a master/slave setup (writes to the master, reads from the slave). Additionally, you want to store sessions when you
create them in Memcache, and try reading from that whenever you can.

#### Configuration

```yml
# The different storage handlers:
services:
    pdo_master:
        class: PDO
        arguments:
            - "mysql:host=%database_master_host%;dbname=%database_master_name%"
            - %database_master_user%
            - %database_master_password%

    pdo_slave:
        class: PDO
        arguments:
            - "mysql:host=%database_slave_host%;dbname=%database_slave_name%"
            - %database_slave_user%
            - %database_slave_password%

    memcache:
        class: Memcache
        calls:
            - [connect, [%memcache_server_host%, %memcache_server_port%]]

    session.storage.pdo_master:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments: [@pdo_master, %session.pdo.db_options%]

    session.storage.pdo_slave:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments: [@pdo_slave, %session.pdo.db_options%]

    session.storage.memcache:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler
        arguments: [@memcache, %session.memcache.options%]

# Session Chain
hautelook_session_storage_chain:
    reader:
        - session.storage.memcache
        - session.storage.pdo_slave
    writer:
        - session.storage.memcache
        - session.storage.pdo_master

framework:
    session:
        handler_id: hautelook.session_storage_chain
```