<?php

namespace model;

class Service {
    public string $id;
    public string $name;
    public string $hostName;
    public SocketProtocol $socketProtocol;
    public int $port;
    public string $icon;
    public bool $enabled;
    public int $timeout;

    public function __construct(?string $id, string $name, string $hostName, SocketProtocol $socketProtocol, int $port, string $icon, bool $enabled = true, int $timeout = 10) {
        $this->id = $id ?: uniqid();
        $this->name = $name;
        $this->hostName = $hostName;
        $this->socketProtocol = $socketProtocol;
        $this->port = $port;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->timeout = $timeout;
    }
}