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

## Screenshot

![](https://evermeet.cx/pub/img/redis-stats.png)

## Acknowledgements

I found the original script at https://gist.github.com/kabel/10023961

