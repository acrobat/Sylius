<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Page\Admin\Crud;

use Behat\Mink\Session;
use Sylius\Behat\Page\SymfonyPage;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Arkadiusz Krakowiak <arkadiusz.krakowiak@lakion.com>
 */
class UpdatePage extends SymfonyPage implements EditPageInterface
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @param Session $session
     * @param array $parameters
     * @param RouterInterface $router
     * @param string $resourceName
     */
    public function __construct(Session $session, array $parameters, RouterInterface $router, $resourceName)
    {
        parent::__construct($session, $parameters, $router);

        $this->resourceName = strtolower($resourceName);
    }

    /**
     * {@inheritdoc}
     */
    public function saveChanges()
    {
        $this->getDocument()->pressButton('Save changes');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteName()
    {
        return sprintf('sylius_admin_%s_update', $this->resourceName);
    }

    /**
     * @return string
     */
    protected function getResourceName()
    {
        return $this->resourceName;
    }
}
