<?php

namespace model\configuration;

class Configuration {
    public array $servers;
    public function __construct(array $servers) {
        $this->servers = $servers;
    }
}