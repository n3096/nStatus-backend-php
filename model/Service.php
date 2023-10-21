<?php

namespace model;

class Service {
    public string $name;
    public string $hostName;
    public SocketProtocol $socketProtocol;
    public int $port;
    public ?int $timeout;

    public function __construct(string $name, string $hostName, SocketProtocol $socketProtocol, int $port, ?int $timeout = null) {
        $this->name = $name;
        $this->hostName = $hostName;
        $this->socketProtocol = $socketProtocol;
        $this->port = $port;
        $this->timeout = $timeout;
    }
}