<?php

namespace Mandryn\Mcp\Connection;

final class DbConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $user,
        public readonly string $password,
        public readonly string $name,
    ) {
    }

    /**
     * mysqli_connect() accepts "host:port" in the hostname argument; the
     * legacy global DBQuery has no port parameter, so this form lets the MCP
     * paging path honour a non-default port.
     */
    public function hostForMysqli(): string
    {
        return $this->port === 3306 ? $this->host : $this->host . ':' . $this->port;
    }
}
