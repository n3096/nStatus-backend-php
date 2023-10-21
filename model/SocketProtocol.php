<?php

namespace model;

enum SocketProtocol: string {
    case TCP = "TCP";
    case HTTP = "HTTP";
    case HTTPS = "HTTPS";
    case UDP = "UDP";
}