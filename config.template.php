<?php
// CONFIG

/*
	Servers are defined as an array
	[ Name, IP/Socket, Port, Password ]

	Name (string):         name shown in drop-down list
	IP/Socket (string):    IP address or socket of the server
	Port (integer):        port of server, use -1 for socket
	Password (string):     auth password for the server (optional
*/

$servers = [
	[ 'Local', '127.0.0.1', 6379 ],
	[ 'Local socket', 'unix:///var/run/redis.sock', -1 ],
	[ 'Local with password', '127.0.0.1', 6379, 'password_here' ],
];

define("DEBUG", false);

// END CONFIG
