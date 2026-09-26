<?php

declare(strict_types=1);

$roots = ["app", "bootstrap", "config", "database", "resources", "routes", "scripts", "tests"];
$checkOnly = in_array("--check", $argv, true);
$changedFiles = [];

$decodeSingleQuoted = static function(string $literal): string {

    $content = substr($literal, 1, -1);
    $decoded = "";
    $length = strlen($content);

    for($index = 0; $index < $length; $index++) {

        $character = $content[$index];

        if($character === "\\" && $index + 1 < $length) {

            $next = $content[$index + 1];

            if($next === "\\" || $next === "'") {

                $decoded .= $next;
                $index++;

                continue;

            }

        }

        $decoded .= $character;

    }

    return $decoded;

};

$encodeDoubleQuoted = static function(string $value): string {

    return "\"".str_replace(
        ["\\", "\"", "$"],
        ["\\\\", "\\\"", "\\$"],
        $value
    )."\"";

};

foreach($roots as $root) {

    $absoluteRoot = dirname(__DIR__).DIRECTORY_SEPARATOR.$root;

    if(!is_dir($absoluteRoot)) {

        continue;

    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach($iterator as $file) {

        if(!$file->isFile() || strtolower($file->getExtension()) !== "php") {

            continue;

        }

        $path = $file->getPathname();

        if(str_contains($path, DIRECTORY_SEPARATOR."bootstrap".DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR)) {

            continue;

        }

        $source = file_get_contents($path);

        if($source === false) {

            throw new RuntimeException("No se pudo leer {$path}.");

        }

        $formatted = "";

        foreach(token_get_all($source) as $token) {

            if(is_array($token)) {

                [$type, $text] = $token;

                if($type === T_CONSTANT_ENCAPSED_STRING && str_starts_with($text, "'")) {

                    $text = $encodeDoubleQuoted($decodeSingleQuoted($text));

                }
                $formatted .= $text;

                continue;

            }

            $formatted .= $token;

        }

        if($formatted === $source) {

            continue;

        }

        $changedFiles[] = $path;

        if(!$checkOnly && file_put_contents($path, $formatted) === false) {

            throw new RuntimeException("No se pudo escribir {$path}.");

        }

    }

}

if($checkOnly && $changedFiles !== []) {

    fwrite(STDERR, "Hay ".count($changedFiles)." archivos PHP con cadenas entre comillas simples.\n");
    exit(1);

}

fwrite(STDOUT, $checkOnly
    ? "Las cadenas PHP cumplen la convención de comillas dobles.\n"
    : "Comillas dobles aplicadas en ".count($changedFiles)." archivos PHP.\n");
