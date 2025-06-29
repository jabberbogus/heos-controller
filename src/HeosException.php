<?php
namespace HeosApp;

use Symfony\Component\Yaml\Yaml;

class HeosException extends \Exception {
    private static array $translations = [];

    public static function fromHeosMessage(string $message): self {
        if (empty(self::$translations)) {
            self::$translations = Yaml::parseFile(__DIR__ . '/../translations/heos_errors.pl.yaml');
        }

        $eid = null;
        $text = 'Unknown error';
        $parts = explode('&', $message);

        foreach ($parts as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $val] = explode('=', $pair, 2);
                if ($key === 'eid') $eid = (int)$val;
                if ($key === 'text') $text = urldecode($val);
            }
        }

        if ($eid !== null && array_key_exists($eid, self::$translations)) {
            $description = self::$translations[$eid];
        } else {
            $description = "Unknown error HEOS";
        }
        return new self("[" . ($eid !== null ? $eid : "no-eid") . "] $description — $text", $eid ?? 0);
    }
}
