<?php declare(strict_types=1);

namespace App\Service;

use App\Application\ApplicationInterface;
use App\Structs\Build;
use App\Structs\BuildInterface;
use App\Structs\LatestBuild;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\RouterInterface;

class BuildsService
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var DownloadCounterService
     */
    private $downloadCounter;

    /**
     * BuildsService constructor.
     *
     * @param RouterInterface        $router
     * @param DownloadCounterService $downloadCounter
     */
    public function __construct(RouterInterface $router, DownloadCounterService $downloadCounter)
    {
        $this->router = $router;
        $this->downloadCounter = $downloadCounter;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return BuildInterface[]
     */
    public function getBuildsForApplication(ApplicationInterface $application): array
    {
        $builds = $this->getBuildsFromFilesystemForApplication($application);

        $latestBuild = $this->findLatestBuild($application, $builds);

        if ($latestBuild !== null) {
            $builds[] = $latestBuild;
        }

        return $builds;
    }

    public function getLatestBuildForApplication(ApplicationInterface $application): ?BuildInterface
    {
        $builds = $this->getBuildsFromFilesystemForApplication($application);

        return $this->findLatestBuild($application, $builds);
    }

    public function getBuildForApplication(ApplicationInterface $application, string $fileName): BuildInterface
    {
        $latestBuild = $this->getLatestBuildForApplication($application);
        if ($latestBuild !== null && $fileName === $latestBuild->getFileName()) {
            return $latestBuild;
        }

        return $this->getBuildForFile($application, $this->getSplFile($application, $fileName));
    }

    public function getPathForBuild(ApplicationInterface $application, string $fileName): string
    {
        return $this->getPathForApplication($application) . \DIRECTORY_SEPARATOR . $fileName;
    }

    public function getPathForApplication(ApplicationInterface $application): string
    {
        return getenv('DATA_PATH') . \DIRECTORY_SEPARATOR . $application->getName();
    }

    public function doesBuildExist(ApplicationInterface $application, string $fileName): bool
    {
        $latestBuild = $this->getLatestBuildForApplication($application);
        if ($latestBuild !== null && $fileName === $latestBuild->getFileName()) {
            return $latestBuild->getFile() !== null;
        }

        return $this->getSplFile($application, $fileName) !== null;
    }

    private function findLatestBuild(ApplicationInterface $application, array $builds): ?LatestBuild
    {
        /** @var Build $highestVersion */
        $highestVersion = null;
        /** @var BuildInterface $build */
        foreach ($builds as $build) {
            if ($highestVersion !== null) {
                if ($this->isNewerThan($build->getMinecraftVersion(), $highestVersion->getMinecraftVersion())) {
                    $highestVersion = $build;
                    continue;
                }

                if ($build->getEpochDate() > $highestVersion->getEpochDate()) {
                    $highestVersion = $build;
                    continue;
                }
            } else {
                $highestVersion = $build;
            }
        }

        if ($highestVersion === null) {
            return null;
        }

        return new LatestBuild(
            $application,
            $highestVersion->getFile(),
            $highestVersion->getDirectLink(),
            $highestVersion->getGrabLink(),
            $highestVersion->getDownloadCounter()
        );
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return BuildInterface[]
     */
    private function getBuildsFromFilesystemForApplication(ApplicationInterface $application): array
    {
        $applicationPath = $this->getPathForApplication($application);

        $finder = new Finder();
        $filesystem = new Filesystem();

        if ($filesystem->exists($applicationPath) === false)  {
            return [];
        }

        $finder->files()->in($applicationPath);

        $builds = [];
        foreach ($finder as $file) {
            $builds[] = $this->getBuildForFile($application, $file);
        }

        return $builds;
    }

    /**
     * Returns true if the first Version is higher than the second Version
     *
     * @param string $versionA
     * @param string $versionB
     *
     * @return bool
     */
    private function isNewerThan(string $versionA, string $versionB): bool
    {
        return version_compare($versionA, $versionB) === 1;
    }

    private function getBuildForFile(ApplicationInterface $application, SplFileInfo $file): BuildInterface
    {
        $directLink = $this->getDirectLinkForFile($application, $file);

        $grabLink = $this->getGrabLinkForFile($application, $file);

        $build = new Build($application, $file, $directLink, $grabLink);

        $build->setDownloadCounter($this->downloadCounter->getCounter($application, $build));

        return $build;
    }

    private function getDirectLinkForFile(ApplicationInterface $application, SplFileInfo $file): string
    {
        return $this->router->generate('files', [
            'applicationName' => $application->getName(),
            'fileName'        => $file->getFilename(),
        ], RouterInterface::ABSOLUTE_URL);
    }

    private function getGrabLinkForFile(ApplicationInterface $application, SplFileInfo $file): string
    {
        return $this->router->generate('grab', [
            'applicationName' => $application->getName(),
            'fileName'        => $file->getFilename(),
        ], RouterInterface::ABSOLUTE_URL);
    }

    private function getSplFile(ApplicationInterface $application, string $fileName): ?SplFileInfo
    {
        $applicationPath = $this->getPathForApplication($application);

        $finder = new Finder();
        $finder->files()->in($applicationPath)->name($fileName);

        if ($finder->count() === 1) {
            /* @noinspection SuspiciousLoopInspection */
            foreach ($finder as $file) {
                return $file;
            }
        }

        return null;
    }
}
