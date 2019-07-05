<?php

namespace APISkeletons\Doctrine\GraphQL\Config;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Context
{
    public function __construct(array $options = [])
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'hydratorSection' => 'default',
            'limit' => 1000,
            'useHydratorCache' => false,
        ]);

        $optionsResolver->setAllowedTypes('hydratorSection', 'string');
        $optionsResolver->setAllowedTypes('limit', 'number');
        $optionsResolver->setAllowedTypes('useHydratorCache', 'boolean');
    }

    public function getHydratorSection()
    {
        return $this->options['hydratorSection'];
    }

    public function getLimit()
    {
        return $this->options['limit'];
    }

    public function getUseHydratorCache()
    {
        return $this->options['useHydratorCache']
    }
}
