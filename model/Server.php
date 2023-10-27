<?php

namespace model;

use helper\UUID4;

class Server {
    public string $id;
    public string $name;
    public array $services;

    public function __construct(?string $id, string $name, array $services) {
        $this->id = UUID4::validate($id) ?: UUID4::generate();
        $this->name = $name;
        $this->services = $services;
    }
}