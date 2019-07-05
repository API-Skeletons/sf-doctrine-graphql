<?php

namespace APISkeletons\Doctrine\GraphQL\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class ConfigurationSkeletonCommandFactory
{
    private $container;

    public __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke()
    {
        die('create command factory');
    }
}
