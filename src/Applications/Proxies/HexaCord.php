<?php


namespace App\Applications\Proxies;

use App\Applications\ApplicationInterface;


class HexaCord implements ApplicationInterface
{
    public function isRecommended(): bool
    {
        return false;
    }

    public function isAbandoned(): bool
    {
        return false;
    }

    public function isExternal(): bool
    {
        return false;
    }

    public function getUrl(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return 'HexaCord';
    }

    public function getCategory(): string
    {
        return 'Proxies';
    }
}