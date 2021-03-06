<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\ThemeBundle\Loader;

use Sylius\Bundle\ThemeBundle\Configuration\Provider\ConfigurationProviderInterface;
use Sylius\Bundle\ThemeBundle\Factory\ThemeAuthorFactoryInterface;
use Sylius\Bundle\ThemeBundle\Factory\ThemeFactoryInterface;
use Sylius\Bundle\ThemeBundle\Model\ThemeAuthor;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Zend\Hydrator\HydrationInterface;

/**
 * @author Kamil Kokot <kamil.kokot@lakion.com>
 */
final class ThemeLoader implements ThemeLoaderInterface
{
    /**
     * @var ConfigurationProviderInterface
     */
    private $configurationProvider;

    /**
     * @var ThemeFactoryInterface
     */
    private $themeFactory;

    /**
     * @var ThemeAuthorFactoryInterface
     */
    private $themeAuthorFactory;

    /**
     * @var HydrationInterface
     */
    private $themeHydrator;

    /**
     * @var CircularDependencyCheckerInterface
     */
    private $circularDependencyChecker;

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     * @param ThemeFactoryInterface $themeFactory
     * @param ThemeAuthorFactoryInterface $themeAuthorFactory
     * @param HydrationInterface $themeHydrator
     * @param CircularDependencyCheckerInterface $circularDependencyChecker
     */
    public function __construct(
        ConfigurationProviderInterface $configurationProvider,
        ThemeFactoryInterface $themeFactory,
        ThemeAuthorFactoryInterface $themeAuthorFactory,
        HydrationInterface $themeHydrator,
        CircularDependencyCheckerInterface $circularDependencyChecker
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->themeFactory = $themeFactory;
        $this->themeAuthorFactory = $themeAuthorFactory;
        $this->themeHydrator = $themeHydrator;
        $this->circularDependencyChecker = $circularDependencyChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $configurations = $this->configurationProvider->getConfigurations();

        $themes = $this->initializeThemes($configurations);
        $themes = $this->hydrateThemes($configurations, $themes);

        $this->checkForCircularDependencies($themes);

        return array_values($themes);
    }

    /**
     * @param array $configurations
     *
     * @return ThemeInterface[]
     */
    private function initializeThemes(array $configurations)
    {
        $themes = [];
        foreach ($configurations as $configuration) {
            /** @var ThemeInterface $theme */
            $themes[$configuration['name']] = $this->themeFactory->createNamed($configuration['name']);
        }

        return $themes;
    }

    /**
     * @param array $configurations
     * @param ThemeInterface[] $themes
     *
     * @return ThemeInterface[]
     */
    private function hydrateThemes(array $configurations, array $themes)
    {
        foreach ($configurations as $configuration) {
            $themeName = $configuration['name'];

            $configuration['parents'] = $this->convertParentsNamesToParentsObjects($themeName, $configuration['parents'], $themes);
            $configuration['authors'] = $this->convertAuthorsArraysToAuthorsObjects($configuration['authors']);

            $themes[$themeName] = $this->themeHydrator->hydrate($configuration, $themes[$themeName]);
        }

        return $themes;
    }

    /**
     * @param ThemeInterface[] $themes
     */
    private function checkForCircularDependencies(array $themes)
    {
        try {
            foreach ($themes as $theme) {
                $this->circularDependencyChecker->check($theme);
            }
        } catch (CircularDependencyFoundException $exception) {
            throw new ThemeLoadingFailedException('Circular dependency found.', 0, $exception);
        }
    }

    /**
     * @param string $themeName
     * @param array $parentsNames
     * @param array $existingThemes
     *
     * @return ThemeInterface[]
     */
    private function convertParentsNamesToParentsObjects($themeName, array $parentsNames, array $existingThemes)
    {
        return array_map(function ($parentName) use ($themeName, $existingThemes) {
            if (!isset($existingThemes[$parentName])) {
                throw new ThemeLoadingFailedException(sprintf(
                    'Unexisting theme "%s" is required by "%s".',
                    $parentName,
                    $themeName
                ));
            }

            return $existingThemes[$parentName];
        }, $parentsNames);
    }

    /**
     * @param array $authorsArrays
     *
     * @return ThemeAuthor[]
     */
    private function convertAuthorsArraysToAuthorsObjects(array $authorsArrays)
    {
        return array_map(function (array $authorArray) {
            return $this->themeAuthorFactory->createFromArray($authorArray);
        }, $authorsArrays);
    }
}
