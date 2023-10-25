<?php

namespace service;

use CurlHandle;
use CurlMultiHandle;
use Exception;
use model\security\nSecure;
use model\Service;
use model\ServiceCheck;
use model\SocketProtocol;
use model\Status;

class ServiceCheckThreaderService {
    static private string $SALT_LIST_FILE_PATH = __DIR__ . '/../tmp/getTemporarySalts.php';
    static private string $PASSWORD = 'PD3&3cfpU*T$b&^*G&KxX8Gn&@TUEMWd7^#x*4J*V&9!!h%*TVidiLx@A8EzkKD9Sr@q2ophVsVT$k$tN@TnA#m&x^Syj2ZJtKvj!ayUcd@QHDsWf4EuxdM3BWfC6%t*';

    private function __construct() {}

    static public function parseService(?string $data, ?string $salt): Service|FALSE {
        if (empty($data) || empty($salt))
            return FALSE;

        $hash = self::$PASSWORD.$salt;
        try {
            $serviceMap = nSecure::decrypt($data, $hash, self::getLocalFileSaltList($hash));
            return new Service($serviceMap['id'], $serviceMap['name'], $serviceMap['hostName'], SocketProtocol::parse($serviceMap['socketProtocol']), $serviceMap['port'], $serviceMap['icon'], $serviceMap['enabled'], $serviceMap['timeout']);
        } catch (Exception) {
            return FALSE;
        }
    }

    public static function checkServicesMultithreaded(array $services): array {
        $salts = nSecure::generateSaltList();
        $headerDate = gmdate('D, d M Y H:i:s T');
        $hash = self::$PASSWORD . $headerDate;

        self::createLocalSaltFile($salts, $hash);

        $multiCurlHandler = curl_multi_init();
        $serviceCurlMap = [];
        foreach($services as $service) {
            $curl = self::getServiceCurlHandle($service, $hash, $salts, $headerDate);
            if ($curl) {
                curl_multi_add_handle($multiCurlHandler, $curl);
                $serviceCurlMap[] = ['service' => $service, 'curl' => $curl];
            }
        }

        self::executeAndWaitMultiCurl($multiCurlHandler);

        $serviceCheckMap = [];
        foreach($serviceCurlMap as $serviceCurlEntry){
            curl_multi_remove_handle($multiCurlHandler, $serviceCurlEntry['curl']);
            $serviceCheck = self::parseCurlResponse($serviceCurlEntry['service'], $serviceCurlEntry['curl'], $headerDate);
            if ($serviceCheck !== null)
                $serviceCheckMap[$serviceCurlEntry['service']->id] = $serviceCheck;
        }
        curl_multi_close($multiCurlHandler);
        return $serviceCheckMap;
    }

    static private function createLocalSaltFile(array $salts, string $hash): void {
        $directoryPath = dirname(self::$SALT_LIST_FILE_PATH);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
        $salt = nSecure::encrypt($salts, $hash, [$hash]);

        $fileHandler = fopen(self::$SALT_LIST_FILE_PATH, "w");
        fwrite($fileHandler, "<?php\nfunction getSalt(): string { return '$salt'; }");
    }

    static private function getLocalFileSaltList(string $hash): array|null {
        try {
            require_once self::$SALT_LIST_FILE_PATH;
            return nSecure::decrypt(getSalt(), $hash, [$hash]);
        } catch (Exception) {
            return null;
        }
    }

    static private function getServiceCurlHandle(Service $service, string $hash, array $salts, string $headerDate): CurlHandle|FALSE {
        $timeout = $service->timeout + 5;
        $serviceHash = nSecure::encrypt($service, $hash, $salts);
        $url = "{$_SERVER['SERVER_NAME']}/status/api/services/$serviceHash/serviceCheck";

        $curl = curl_init();
        if ($curl === FALSE) {
            return FALSE; //TODO: add log
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Date: $headerDate"]);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_NOPROGRESS, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);

        return $curl;
    }

    static private function executeAndWaitMultiCurl(CurlMultiHandle $multiCurlHandler): void {
        do {
            $status = curl_multi_exec($multiCurlHandler, $isRunning);
            if ($isRunning) curl_multi_select($multiCurlHandler);
        } while ($isRunning && $status == CURLM_OK);
    }

    private static function parseCurlResponse(Service $service, CurlHandle $curlHandle, string $headerDate): ?ServiceCheck {
        $httpResponseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        if ($httpResponseCode < 200 || $httpResponseCode >= 300) {
            // TODO: add log
        } else {
            $response = curl_multi_getcontent($curlHandle);
            $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $headerSize);

            preg_match('/Date:.*/m', $header, $dateMatches);
            if (!isset($dateMatches[0]) || $dateMatches[0] != $headerDate) {
                // TODO: add log
            } else {
                try {
                    $serviceCheckMap = json_decode(substr($response, $headerSize), TRUE);
                    return new ServiceCheck(
                        $service,
                        $serviceCheckMap['fullHostName'],
                        $serviceCheckMap['timestamp'],
                        $serviceCheckMap['latency'],
                        $serviceCheckMap['ipv4'],
                        $serviceCheckMap['ipv6'],
                        $serviceCheckMap['forwardedHost'],
                        Status::parse($serviceCheckMap['status']),
                        $serviceCheckMap['response'],
                        $serviceCheckMap['notes']);
                } catch (Exception) {
                    // TODO: add log
                }
            }
        }
        return null;
    }
}