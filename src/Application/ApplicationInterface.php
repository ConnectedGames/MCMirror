<?php

namespace App\Application;

interface ApplicationInterface
{
    public function isRecommended(): bool;

    public function isAbandoned(): bool;

    public function isExternal(): bool;

    public function getUrl(): ?string;

    public function getName(): string;

    public function getCategory(): string;

    public function getOfficialLinks(): array;

    /**
     * @return string|array
     */
    public function getVersionRegex();

    public function getVersionGroupOverride(): array;
}
