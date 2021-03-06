<?php declare(strict_types=1);

namespace App\CompilerPass;

use App\Application\JsonApplication;
use JsonException;
use RuntimeException;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use function in_array;
use const DIRECTORY_SEPARATOR;

class RegisterJsonApplicationsPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     * @throws JsonException
     */
    public function process(ContainerBuilder $container): void
    {
        $rootDir = $container->getParameter('kernel.project_dir');
        $applicationsPath = $rootDir . '/applications';

        $categoriesFile = $applicationsPath . DIRECTORY_SEPARATOR . 'Categories.json';

        $container->addResource(new DirectoryResource($applicationsPath));
        $container->addResource(new FileResource($categoriesFile));

        $existingCategories = [];
        $folderFinder = new Finder();
        $folderFinder->directories()->in($applicationsPath);
        foreach ($folderFinder as $folder) {
            if (!$folder->isDir()) {
                continue;
            }

            $container->addResource(new DirectoryResource($folder->getRealPath()));

            $fileFinder = new Finder();
            $fileFinder->files()->in($folder->getRealPath());
            foreach ($fileFinder as $file) {
                try {
                    $jsonData = json_decode($file->getContents(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new RuntimeException(sprintf('Invalid JSON File `%s`: %s', $file->getBasename(), $e->getMessage()));
                }
                $existingCategories[] = $jsonData['category'];

                $container->addResource(new FileResource($file->getRealPath()));

                $container->register('App\\Application\\JsonApplication\\' . $jsonData['name'])
                    ->setClass(JsonApplication::class)
                    ->setFactory([__CLASS__, 'createJsonApplication'])
                    ->setArgument(0, $jsonData)
                    ->addTag('app.application');
            }
        }

        $categoryOrder = json_decode(file_get_contents($categoriesFile), true, 512, JSON_THROW_ON_ERROR);
        $existingCategories = array_keys(array_flip($existingCategories));

        $container->setParameter('application.categories', $this->getCategories($existingCategories, $categoryOrder));
    }

    public static function createJsonApplication(array $jsonData): JsonApplication
    {
        return new JsonApplication($jsonData);
    }

    private function getCategories(array $existingCategories, array $categoryOrder): array
    {
        $orderedCategories = [];

        foreach ($categoryOrder as $orderKey => $orderCategory) {
            if (in_array($orderCategory, $existingCategories, true)) {
                $orderedCategories[$orderKey] = $orderCategory;
            }
        }
        foreach ($existingCategories as $key => $category) {
            if (in_array($category, $categoryOrder, true)) {
                unset($existingCategories[$key]);
                continue;
            }

            $orderedCategories[] = $category;
        }

        return $orderedCategories;
    }
}
