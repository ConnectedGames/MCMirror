<?php


namespace App\Service;


use App\Application\ApplicationInterface;
use App\Structs\BuildInterface;

interface DownloadCounterInterface
{
    public function boot(): void;

    public function getName(): string;

    public function increaseCounter(ApplicationInterface $application, BuildInterface $build): void;

    public function getCount(ApplicationInterface $application, BuildInterface $build): int;

    public function getCountForApplication(ApplicationInterface $application): int;

    public function getTotalCount(): int;
}