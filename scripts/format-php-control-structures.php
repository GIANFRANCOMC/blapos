<?php

declare(strict_types=1);

$roots = ["app", "bootstrap", "config", "database", "resources", "routes", "scripts", "tests"];
$checkOnly = in_array("--check", $argv, true);
$changedFiles = [];
$controlTokens = [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH, T_CATCH];
$blockStarterTokens = [
    ...$controlTokens,
    T_FUNCTION,
    T_TRY,
    T_ELSE,
    T_FINALLY,
    T_DO,
];

$compactContinuationTokens = [T_ELSEIF, T_ELSE, T_CATCH, T_FINALLY];
$textOf = static fn(array|string $token): string => is_array($token) ? $token[1] : $token;
$typeOf = static fn(array|string $token): ?int => is_array($token) ? $token[0] : null;
$isWhitespace = static fn(array|string $token): bool => is_array($token) && $token[0] === T_WHITESPACE;
$isIgnored = static fn(array|string $token): bool => is_array($token)
    && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);

$previousSignificantIndex = static function(array $tokens, int $index) use ($isIgnored): ?int {

    for($candidate = $index - 1; $candidate >= 0; $candidate--) {

        if(!$isIgnored($tokens[$candidate])) {

            return $candidate;

        }

    }

    return null;

};

$nextSignificantIndex = static function(array $tokens, int $index) use ($isIgnored): ?int {

    $tokenCount = count($tokens);

    for($candidate = $index + 1; $candidate < $tokenCount; $candidate++) {

        if(!$isIgnored($tokens[$candidate])) {

            return $candidate;

        }

    }

    return null;

};

$structuredStarterIndex = static function(array $tokens, int $openingIndex) use (
    $blockStarterTokens,
    $previousSignificantIndex,
    $textOf,
    $typeOf
): ?int {

    $previousIndex = $previousSignificantIndex($tokens, $openingIndex);

    if($previousIndex === null) {

        return null;

    }

    if($textOf($tokens[$previousIndex]) === ")") {

        $parenthesisDepth = 0;

        for($index = $previousIndex; $index >= 0; $index--) {

            $text = $textOf($tokens[$index]);

            if($text === ")") {

                $parenthesisDepth++;

                continue;

            }

            if($text !== "(") {

                continue;

            }

            $parenthesisDepth--;

            if($parenthesisDepth !== 0) {

                continue;

            }

            $keywordIndex = $previousSignificantIndex($tokens, $index);

            if(
                $keywordIndex !== null
                && in_array($typeOf($tokens[$keywordIndex]), $blockStarterTokens, true)
            ) {

                return $keywordIndex;

            }

            break;

        }

    }

    for($index = $previousIndex; $index >= 0; $index--) {

        $text = $textOf($tokens[$index]);

        if(in_array($text, [";", "{", "}"], true)) {

            break;

        }

        if(in_array($typeOf($tokens[$index]), $blockStarterTokens, true)) {

            return $index;

        }

    }

    return null;

};

$normalizeImports = static function(string $source): string {

    $pattern = "/^(use\s+(?:(?:function|const)\s+)?)([A-Za-z_\\x80-\\xff][A-Za-z0-9_\\x80-\\xff]*(?:\\\\[A-Za-z_\\x80-\\xff][A-Za-z0-9_\\x80-\\xff]*)+)(\s+as\s+[A-Za-z_\\x80-\\xff][A-Za-z0-9_\\x80-\\xff]*)?;$/m";
    $normalized = preg_replace_callback($pattern, static function(array $matches): string {

        $lastSeparator = strrpos($matches[2], "\\");

        if($lastSeparator === false) {

            return $matches[0];

        }

        $namespace = substr($matches[2], 0, $lastSeparator);

        $import = substr($matches[2], $lastSeparator + 1).($matches[3] ?? "");

        return $matches[1].$namespace."\\{".$import."};";

    }, $source);

    if($normalized === null) {

        throw new RuntimeException("No se pudieron normalizar los imports.");

    }

    return $normalized;

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

        $originalSource = file_get_contents($path);

        if($originalSource === false) {

            throw new RuntimeException("No se pudo leer {$path}.");

        }

        $source = str_replace(["\r\n", "\r"], "\n", $originalSource);

        $tokens = token_get_all($source);

        $tokenCount = count($tokens);
        $lineEnding = "\n";

        for($index = 0; $index < $tokenCount; $index++) {

            $token = $tokens[$index];
            $nextIndex = $index + 1;

            if(
                is_array($token)
                && in_array($token[0], $controlTokens, true)
                && $nextIndex + 1 < $tokenCount
                && $isWhitespace($tokens[$nextIndex])
                && $textOf($tokens[$nextIndex + 1]) === "("
            ) {

                $tokens[$nextIndex][1] = "";

            }

            if(
                $textOf($token) === "!"
                && $nextIndex < $tokenCount
                && $isWhitespace($tokens[$nextIndex])
            ) {

                $tokens[$nextIndex][1] = "";

            }

            if(
                $textOf($token) === "}"
                && $nextIndex + 1 < $tokenCount
                && $isWhitespace($tokens[$nextIndex])
                && in_array($typeOf($tokens[$nextIndex + 1]), $compactContinuationTokens, true)
            ) {

                $tokens[$nextIndex][1] = "";

            }

            if(
                is_array($token)
                && in_array($token[0], [T_FUNCTION, T_FN], true)
                && $nextIndex + 1 < $tokenCount
                && $isWhitespace($tokens[$nextIndex])
                && $textOf($tokens[$nextIndex + 1]) === "("
            ) {

                $tokens[$nextIndex][1] = "";

            }

        }

        $withBlankLine = static function(string $whitespace) use ($lineEnding): string {

            if(substr_count(str_replace(["\r\n", "\r"], "\n", $whitespace), "\n") >= 2) {

                return $whitespace;

            }

            preg_match("/[\t ]*$/", $whitespace, $matches);

            return $lineEnding.$lineEnding.($matches[0] ?? "");

        };

        for($index = 0; $index < $tokenCount; $index++) {

            $token = $tokens[$index];

            if(is_array($token) && in_array($token[0], $blockStarterTokens, true)) {

                $whitespaceIndex = $index - 1;
                $previousIndex = $previousSignificantIndex($tokens, $index);
                $previousText = $previousIndex === null ? null : $textOf($tokens[$previousIndex]);
                $isContinuation = in_array($token[0], $compactContinuationTokens, true);

                if(
                    !$isContinuation
                    && $whitespaceIndex >= 0
                    && $isWhitespace($tokens[$whitespaceIndex])
                    && str_contains($textOf($tokens[$whitespaceIndex]), "\n")
                    && !in_array($previousText, [null, "{", ":"], true)
                ) {

                    $tokens[$whitespaceIndex][1] = $withBlankLine($textOf($tokens[$whitespaceIndex]));

                }

            }

            if(!is_array($token) || $token[0] !== T_VARIABLE) {

                continue;

            }

            $whitespaceIndex = $index - 1;

            $previousIndex = $previousSignificantIndex($tokens, $index);

            if(
                $whitespaceIndex < 0
                || !$isWhitespace($tokens[$whitespaceIndex])
                || !str_contains($textOf($tokens[$whitespaceIndex]), "\n")
                || $previousIndex === null
                || $textOf($tokens[$previousIndex]) !== ";"
            ) {

                continue;

            }

            $statementStart = $previousSignificantIndex($tokens, $previousIndex);

            $parenthesisDepth = 0;
            $bracketDepth = 0;
            $braceDepth = 0;

            while($statementStart !== null) {

                $statementToken = $textOf($tokens[$statementStart]);

                if($statementToken === ")") {

                    $parenthesisDepth++;

                }elseif($statementToken === "(" && $parenthesisDepth > 0) {

                    $parenthesisDepth--;

                }elseif($statementToken === "]") {

                    $bracketDepth++;

                }elseif($statementToken === "[" && $bracketDepth > 0) {

                    $bracketDepth--;

                }elseif($statementToken === "}") {

                    $braceDepth++;

                }elseif($statementToken === "{" && $braceDepth > 0) {

                    $braceDepth--;

                }elseif(
                    $parenthesisDepth === 0
                    && $bracketDepth === 0
                    && $braceDepth === 0
                    && in_array($statementToken, [";", "{"], true)
                ) {

                    break;

                }

                $statementStart = $previousSignificantIndex($tokens, $statementStart);

            }
            $statementStart = $statementStart === null ? 0 : $statementStart + 1;

            $statementText = "";

            for($statementIndex = $statementStart; $statementIndex <= $previousIndex; $statementIndex++) {

                $statementText .= $textOf($tokens[$statementIndex]);

            }

            if(str_contains(trim($statementText), "\n")) {

                $tokens[$whitespaceIndex][1] = $withBlankLine($textOf($tokens[$whitespaceIndex]));

            }

        }

        $lineIndents = [];

        $linePrefix = "";

        foreach($tokens as $index => $token) {

            $lineIndents[$index] = preg_match("/^[\t ]*/", $linePrefix, $matches) === 1
                ? $matches[0]
                : "";

            $text = $textOf($token);

            if(preg_match("/(?:\r\n|\n|\r)([^\r\n]*)$/", $text, $matches) === 1) {

                $linePrefix = $matches[1];

            }else {

                $linePrefix .= $text;

            }

        }

        $qualifiedOpenings = [];

        $matchingOpening = [];
        $braceStack = [];

        foreach($tokens as $index => $token) {

            $text = $textOf($token);

            if(
                is_array($token)
                && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)
            ) {

                $braceStack[] = [$index, false];

                continue;

            }

            if($text === "{") {

                $starterIndex = $structuredStarterIndex($tokens, $index);
                $qualifiedOpenings[$index] = $starterIndex;
                $braceStack[] = [$index, $starterIndex !== null];

                continue;

            }

            if($text !== "}" || $braceStack === []) {

                continue;

            }

            [$openingIndex, $qualified] = array_pop($braceStack);

            if($qualified) {

                $matchingOpening[$index] = $openingIndex;

            }

        }

        foreach($qualifiedOpenings as $openingIndex => $starterIndex) {

            if($starterIndex === null) {

                continue;

            }

            $contentIndex = $nextSignificantIndex($tokens, $openingIndex);

            if($contentIndex === null || $textOf($tokens[$contentIndex]) === "}") {

                continue;

            }

            $whitespaceIndex = $openingIndex + 1;

            $openingIndent = $lineIndents[$starterIndex];
            $contentIndent = $openingIndent."    ";

            if($whitespaceIndex < $tokenCount && $isWhitespace($tokens[$whitespaceIndex])) {

                if(preg_match("/([\t ]*)$/", $textOf($tokens[$whitespaceIndex]), $matches) === 1
                    && str_contains($textOf($tokens[$whitespaceIndex]), "\n")) {

                    $contentIndent = $matches[1];

                }

                $tokens[$whitespaceIndex][1] = $lineEnding.$lineEnding.$contentIndent;

            }else {

                $tokens[$openingIndex] = $textOf($tokens[$openingIndex]).$lineEnding.$lineEnding.$contentIndent;

            }

        }

        foreach($matchingOpening as $closingIndex => $openingIndex) {

            $contentIndex = $previousSignificantIndex($tokens, $closingIndex);

            if($contentIndex === null || $contentIndex === $openingIndex) {

                continue;

            }

            $starterIndex = $qualifiedOpenings[$openingIndex];

            $closingIndent = $starterIndex === null ? "" : $lineIndents[$starterIndex];
            $whitespaceIndex = $closingIndex - 1;

            if($whitespaceIndex >= 0 && $isWhitespace($tokens[$whitespaceIndex])) {

                $tokens[$whitespaceIndex][1] = $lineEnding.$lineEnding.$closingIndent;

            }else {

                $tokens[$closingIndex] = $lineEnding.$lineEnding.$closingIndent.$textOf($tokens[$closingIndex]);

            }

        }

        $formatted = implode("", array_map($textOf, $tokens));

        $formatted = preg_replace(
            "/@(if|elseif|for|foreach|while|switch)\s+\(/",
            "@$1(",
            $formatted
        );

        if($formatted === null) {

            throw new RuntimeException("No se pudieron normalizar las directivas Blade de {$path}.");

        }

        $formatted = $normalizeImports($formatted);

        if($formatted === $originalSource) {

            continue;

        }

        $changedFiles[] = $path;

        if(!$checkOnly && file_put_contents($path, $formatted) === false) {

            throw new RuntimeException("No se pudo escribir {$path}.");

        }

    }

}

if($checkOnly && $changedFiles !== []) {

    fwrite(
        STDERR,
        "Hay ".count($changedFiles)." archivos PHP fuera de la convencion estructural:\n"
            .implode("\n", array_map(fn(string $path) => "- {$path}", $changedFiles))
            ."\n"
    );
    exit(1);

}

fwrite(STDOUT, $checkOnly
    ? "Las estructuras PHP cumplen la convencion del proyecto.\n"
    : "Estructuras PHP normalizadas en ".count($changedFiles)." archivos.\n");
