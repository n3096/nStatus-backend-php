<?php

namespace model;

class Server {
    public string $id;
    public string $name;
    public array $services;

    public function __construct(?string $id, string $name, array $services) {
        $this->id = $id ?: uniqid();
        $this->name = $name;
        $this->services = $services;
    }
}