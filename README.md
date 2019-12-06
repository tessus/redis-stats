# redis-stats

## Features

- lightweight
- no PHP redis module required
- connection via IP/port or socket
- password support (including Redis 6 ACLs)
- show details
- flush database (async support)
- flush instance (async support)
- command mapping support (when rename-command is used on the server)
- auto refresh

## Installation

```
git clone --depth 1 https://github.com/tessus/redis-stats.git
cd redis-stats
cp config.template.php config.php
```

## Configuration

### Server information

Servers are defined as an array. There are a few examples in the `config.template.php` file.

Field     | Type          | Description
----------|---------------|-----------------------------------------------
Name      | string        | name shown in drop-down list
IP/Socket | string        | IP address or socket (`unix://`) of the server
Port      | integer       | port of server, use -1 for socket
Password  | string, array | credentials for the server (optional)<br>string: `password`<br>array: `['user', 'password']` for Redis ACLs

e.g.:

```
$servers = [
	[ 'Local', '127.0.0.1', 6379 ],
	[ 'Local socket', 'unix:///var/run/redis.sock', -1 ],
	[ 'Local with password', '127.0.0.1', 6379, 'password_here' ],
	[ 'Local with user and password', '127.0.0.1', 6379, ['username', 'password_here'] ],
];
```

### Misc options (boolean)

Name             | Default   | Description
-----------------|-----------|-----------------------------------------
FLUSHDB          | true      | Show a 'Flush' button for databases
CONFIRM_FLUSHDB  | true      | Ask for confirmation before flushing database
FLUSHALL         | true      | Show a 'Flush All' button for the instance
CONFIRM_FLUSHALL | true      | Ask for confirmation before flushing the entire instance
DEBUG            | false     | debug mode - you don't want to set this to true!

### Command mapping

In case commands have been renamed on the server, there's support to map these commands in the config file.

e.g.:

```
$command = [
	'FLUSHDB'  => 'fdb-5dea06694ff64',
	'FLUSHALL' => 'fa-5dea067c9bbd6',
	'AUTH'     => '',
];
```

## Screenshot

![](https://evermeet.cx/pub/img/redis-stats.png)

## Acknowledgements

I found the original script at https://gist.github.com/kabel/10023961

