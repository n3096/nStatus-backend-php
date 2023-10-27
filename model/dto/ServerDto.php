<?php

namespace model\dto;

use helper\UUID4;
use model\Server;

class ServerDto {
    public string $id;
    public string $name;
    public array $serviceIds;

    public function __construct(Server $server) {
        $this->id = UUID4::validate($server->id);
        $this->name = $server->name;
        $this->serviceIds = array_map(function($service) { return $service->id; }, $server->services);
    }
}