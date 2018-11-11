<?php

namespace App\Application;

class JsonApplication implements ApplicationInterface
{
    /**
     * @var array
     */
    private $jsonData;


    /**
     * JsonApplication constructor.
     * @param array $jsonData
     */
    public function __construct(array $jsonData)
    {
        $this->jsonData = $jsonData;
    }

    public function isRecommended(): bool
    {
        return $this->jsonData['recommended'] ?? false;
    }

    public function isAbandoned(): bool
    {
        return $this->jsonData['abandoned'] ?? true;
    }

    public function isExternal(): bool
    {
        return $this->jsonData['external'] ?? $this->getUrl() !== null;
    }

    public function getUrl(): ?string
    {
        return $this->jsonData['url'] ?? null;
    }

    public function getName(): string
    {
        return $this->jsonData['name'];
    }

    public function getCategory(): string
    {
        return $this->jsonData['category'] ?? 'Other';
    }

    public function getOfficialLinks(): array
    {
        return $this->jsonData['officialLinks'] ?? [];
    }

    /**
     * @return string|array
     */
    public function getVersionRegex()
    {
        return $this->jsonData['versionRegex'];
    }

    public function getVersionGroupOverride(): array
    {
        return $this->jsonData['versionGroupOverride'] ?? [];
    }
}