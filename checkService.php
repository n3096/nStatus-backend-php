<?php
require_once 'autoloader.php';

use model\DateTimeSerializable;
use model\ServiceCheck;
use model\Service;
use model\SocketProtocol;
use model\Status;

error_reporting(E_ERROR);

ob_start();
function check(Service $service): ?ServiceCheck {
    $timeout = $service->timeout ?? 10;

    $notes = [];
    $response = [];
    $forwardedHost = getForwardedHost($service);
    $actualHostName = getActualHostName($forwardedHost ?: $service->hostName, $service->socketProtocol);
    $ipv4 = getIpv4($service);
    $ipv6 = getIpv6($service);

    $dateTime = new DateTimeSerializable();
    $latencyStart = microtime(true);
    $resource = fsockopen($actualHostName, $service->port, $errorCode, $errMessage, $timeout);
    $latency = intval((microtime(true) - $latencyStart) * 1000); // convert to micro seconds

    if (!$resource) {
        $status = Status::UNREACHABLE;
    } else {
        $status = Status::REACHABLE;
        $response = getResponse($resource, $service, $status, $notes);
        fclose($resource);
    }

    if($errorCode != 10061) { // filter common unreachable error code
        if ($errorCode)
            $notes["errorCode"] = $errorCode;
        if ($errMessage)
            $notes["errMessage"] = $errMessage;
    }
    
    if ($status === Status::REACHABLE && sizeof($notes) > 0)
        $status = Status::WARNING;

    if(empty($response))
        $response = (object)[];

    return new ServiceCheck($service->hostName, $service->port, $service->socketProtocol, $actualHostName, $dateTime, $latency, $ipv4, $ipv6, $forwardedHost, $status, $response, $notes);
}

function getActualHostName(string $hostName, SocketProtocol $socketProtocol): string {
    return ($socketProtocol === SocketProtocol::HTTPS ? "ssl://" : "") . $hostName;
}

function getIpv4(Service $service): string {
    $host = gethostbyname($service->hostName);
    if ($host === $service->hostName) {
        $host = dns_get_record($host, DNS_A)[0]["ip"] ?? "";
    }
    return $host;
}

function getIpv6(Service $service): string {
    return dns_get_record($service->hostName, DNS_AAAA)[0]["ipv6"] ?? "";
}

function getForwardedHost(Service $service): string {
    return dns_get_record($service->hostName, DNS_A)[0]["host"] ?? dns_get_record($service->hostName, DNS_AAAA)[0]["host"] ?? "";
}

function getResponse($resource, Service $service, Status &$status, array &$notes): array {
    if (!is_resource($resource))
        throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

    return match ($service->socketProtocol) {
        SocketProtocol::HTTP, SocketProtocol::HTTPS => getResponseForHttpAndHttps($resource, $service, $status, $notes),
        default => [],
    };
}

function getResponseForHttpAndHttps($resource, Service $service, Status &$status, array &$notes): array {
    if (!is_resource($resource))
        throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

    $result = [];

    $request = "GET / HTTP/1.1\r\n";
    $request .= "Host: $service->hostName\r\n";
    $request .= "Connection: close\r\n";
    $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n";
    $request .= "\r\n";
    fwrite($resource, $request);

    $response = '';
    while (!feof($resource)) {
        $response .= fgets($resource, 128);
    }

    preg_match('/HTTP\/1\.\d (\d{3})/', $response, $statusCodeMatches);
    $httpStatusCode = isset($statusCodeMatches[1]) ? (int)$statusCodeMatches[1] : null;

    if ($httpStatusCode) {
        $result["httpStatusCode"] = $httpStatusCode;
        if ($httpStatusCode >= 500) {
            $status = Status::UNREACHABLE;
            $notes[] = "Can't ensure reachability on server error";
        } elseif ($httpStatusCode >= 400) {
            $notes[] = "Remove client error on request";
        } elseif ($httpStatusCode >= 300) {
            $notes[] = "Can't ensure reachability on forwarding";
            preg_match('/Location:.*/m', $response, $locationMatches);
            if(isset($locationMatches[0]))
                $notes[] = trim($locationMatches[0]);
        }
    } else {
        $notes[] = "Could not parse http status code";
    }
    return $result;
}

$service = new Service(null, "Phiwi", "www.phiwi.de", SocketProtocol::HTTPS, 443, "server");
echo json_encode(check($service));
echo "\n";

$service2 = new Service(null, "Phiwi", "phiwi.de", SocketProtocol::HTTPS, 443, "server");
echo json_encode(check($service2));
echo "\n";

$service3 = new Service(null, "MC", "localhost", SocketProtocol::UDP, 25565, "server");
echo json_encode(check($service3));
echo "\n";

//$service4 = new Service("GrieferGames.de", SocketProtocol::UDP, 25565);
//echo json_encode(check($service4));
//echo "\n";

$service5 = new Service(null, "Google", "www.google.com", SocketProtocol::HTTPS, 443, "server");
echo json_encode(check($service5));
echo "\n";