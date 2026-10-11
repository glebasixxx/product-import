<?php

namespace App\Exceptions;

class RowValidationException extends \RuntimeException
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly ?string $externalCode = null,
    ) {
        parent::__construct(implode(' ', $messages));
    }
}
