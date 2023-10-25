<?php

namespace service;

use Exception;
use model\security\nSecure;
use model\Service;
use model\SocketProtocol;

class ServiceCheckThreaderService {
    static private string $SALT_LIST_FILE_PATH = __DIR__ . '/../tmp/getTemporarySalts.php';
    static private string $PASSWORD = 'PD3&3cfpU*T$b&^*G&KxX8Gn&@TUEMWd7^#x*4J*V&9!!h%*TVidiLx@A8EzkKD9Sr@q2ophVsVT$k$tN@TnA#m&x^Syj2ZJtKvj!ayUcd@QHDsWf4EuxdM3BWfC6%t*';
    private function __construct() {}

    static public function parseService(?string $data, ?string $salt): ?Service {
        if (empty($data) || empty($salt))
            return null;

        $hash = self::$PASSWORD.$salt;
        try {
            $serviceMap = nSecure::decrypt($data, $hash, self::getLocalFileSaltList($hash));
            return new Service($serviceMap['id'], $serviceMap['name'], $serviceMap['hostName'], SocketProtocol::parse($serviceMap['socketProtocol']), $serviceMap['port'], $serviceMap['icon'], $serviceMap['enabled'], $serviceMap['timeout']);
        } catch (Exception) {
            return null;
        }
    }

    public static function checkServicesMultithreaded(array $services): void {
        $salts = nSecure::generateSaltList();
        $date = gmdate('D, d M Y H:i:s T');
        $hash = self::$PASSWORD . $date;

        self::createLocalSaltFile($salts, $hash);
        foreach($services as $service) {
            self::checkServiceCurl($service, $hash, $salts, $date);
        }
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

    static private function checkServiceCurl(Service $service, string $hash, array $salts, string $headerDate): void {
        var_dump(nSecure::encrypt($service, $hash, $salts));
    }
}