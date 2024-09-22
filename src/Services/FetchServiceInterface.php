<?php

namespace Src\Services;

interface FetchServiceInterface
{
    public function __construct(string $token);

    public function handle(): void;
}