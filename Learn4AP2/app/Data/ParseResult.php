<?php

namespace App\Data;

final readonly class ParseResult
{
    /**
     * @param  list<CardRowData>  $rows
     * @param  list<ImportError>  $errors
     */
    public function __construct(
        public array $rows,
        public array $errors,
    ) {}
}
