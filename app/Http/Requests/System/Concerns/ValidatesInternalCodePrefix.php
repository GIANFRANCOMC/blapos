<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Concerns;

use App\Services\System\Base\{InternalCodeService};
use Closure;

trait ValidatesInternalCodePrefix {

    protected function internalCodePrefixRule(): Closure {

        $id = $this->route("id");
        return InternalCodeService::prefixRule(
            $this->internalCodeEntity(),
            $id ? (int) $id : null,
            in_array($this->input("internal_code_without_prefix"), [true, 1, "1"], true)
        );

    }

    protected function internalCodeWhitespaceRule(): string {

        return InternalCodeService::WITHOUT_WHITESPACE;

    }

    abstract protected function internalCodeEntity(): string;

}
