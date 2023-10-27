<?php

namespace helper\mapper;

use model\dto\ServerDto;
use model\Server;

class ServerDtoMapper {
    public function __invoke(Server $server): ServerDto {
        return new ServerDto($server);
    }

    static public function map(): callable {
        return new ServerDtoMapper();
    }
}