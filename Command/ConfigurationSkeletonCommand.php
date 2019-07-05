<?php

namespace APISkeletons\Doctrine\GraphQL\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use APISkeletons\Doctrine\GraphQL\Hydrator\Strategy;
use APISkeletons\Doctrine\GraphQL\Hydrator\Filter;

final class ConfigurationSkeletonCommand extends Command
{
    protected static $defaultName = 'graphql:config-skeleton';
    private $io;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create GraphQL configuration skeleton')
            ->addArgument(
                'hydrator-sections',
                InputArgument::OPTIONAL,
                'A comma delimited list of sections to generate'
            )
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'Defaults to doctrine.entitymanager.orm_default'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Create GraphQL configuration skeleton');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $connection = $input->getArgument('connection') ?? 'default';
        $hydratorSections = $input->getArgument('hydrator-sections') ?? 'default';

        try {
            $objectManager = $this->container->getDoctrine()->getManager($connection);
        } catch (RuntimeException $e) {
            $this->io->caution('Invalid connection name');

            return;
        } catch (Exception $e) {
            die('Invalid exception for connection name: ' . get_class($e));
        }

        // Sort entity names
        $metadata = $objectManager->getMetadataFactory()->getAllMetadata();
        usort($metadata, function ($a, $b) {
            return $a->getName() <=> $b->getName();
        });

        foreach (explode(',', $hydratorSections) as $section) {
            foreach ($metadata as $classMetadata) {
                $hydratorAlias = 'APISkeletons\\Doctrine\\GraphQL\\Hydrator\\'
                    . str_replace('\\', '_', $classMetadata->getName());

                $strategies = [];
                $filters = [];
                $documentation = ['_entity' => ''];

                // Sort field names
                $fieldNames = $classMetadata->getFieldNames();
                usort($fieldNames, function ($a, $b) {
                    if ($a == 'id') {
                        return -1;
                    }

                    if ($b == 'id') {
                        return 1;
                    }

                    return $a <=> $b;
                });

                foreach ($fieldNames as $fieldName) {
                    $documentation[$fieldName] = '';
                    $fieldMetadata = $classMetadata->getFieldMapping($fieldName);

                    // Handle special named fields
                    if ($fieldName == 'password' || $fieldName == 'secret') {
                        $filters['password'] = [
                            'condition' => 'and',
                            'filter' => Filter\Password::class,
                        ];
                        continue;
                    }

                    // Handle all other fields
                    switch ($fieldMetadata['type']) {
                        case 'tinyint':
                        case 'smallint':
                        case 'integer':
                        case 'int':
                        case 'bigint':
                            $strategies[$fieldName] = Strategy\ToInteger::class;
                            break;
                        case 'boolean':
                            $strategies[$fieldName] = Strategy\ToBoolean::class;
                            break;
                        case 'decimal':
                        case 'float':
                            $strategies[$fieldName] = Strategy\ToFloat::class;
                            break;
                        case 'string':
                        case 'text':
                        case 'datetime':
                        default:
                            $strategies[$fieldName] = Strategy\FieldDefault::class;
                            break;
                    }
                }

                // Sort association Names
                $associationNames = $classMetadata->getAssociationNames();
                usort($associationNames, function ($a, $b) {
                    return $a <=> $b;
                });

                foreach ($associationNames as $associationName) {
                    $mapping = $classMetadata->getAssociationMapping($associationName);

                    // See comment on NullifyOwningAssociation for details of why this is done
                    if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
                        $strategies[$associationName] = Strategy\NullifyOwningAssociation::class;
                    } else {
                        $strategies[$associationName] = Strategy\AssociationDefault::class;
                    }
                }

                $filters['default'] = [
                    'condition' => 'and',
                    'filter' => Filter\FilterDefault::class,
                ];

                $config['zf-doctrine-graphql-hydrator'][$hydratorAlias][$section] = [
                    'entity_class' => $classMetadata->getName(),
                    'object_manager' => $objectManagerAlias,
                    'by_value' => true,
                    'use_generated_hydrator' => true,
                    'hydrator' => null,
                    'naming_strategy' => null,
                    'strategies' => $strategies,
                    'filters' => $filters,
                    'documentation' => $documentation,
                ];
            }
        }

        $configObject = new Config($config);
        $writer = new PhpArray();
        $writer->setUseBracketArraySyntax(true);
        $writer->setUseClassNameScalars(true);

        echo $writer->toString($configObject);
    }
}
