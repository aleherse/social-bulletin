<?php

declare(strict_types=1);

namespace App\Http\OpenApi;

use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;

/**
 * Describes a model as a JSON:API `attributes` object: the same schema as the model, minus its `id`,
 * which JSON:API carries on the resource itself.
 */
final readonly class JsonApiAttributesModelDescriber implements ModelDescriberInterface, ModelRegistryAwareInterface
{
    public const NAME_SUFFIX = 'Attributes';

    public function __construct(
        private ModelDescriberInterface&ModelRegistryAwareInterface $objectDescriber,
    ) {
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->objectDescriber->setModelRegistry($modelRegistry);
    }

    public function supports(Model $model): bool
    {
        return null !== $model->name && str_ends_with($model->name, self::NAME_SUFFIX);
    }

    public function describe(Model $model, Schema $schema): void
    {
        $this->objectDescriber->describe($model, $schema);

        // swagger-php leaves a placeholder string in unset fields, which its annotations do not admit
        /** @var mixed $properties */
        $properties = $schema->properties;
        /** @var mixed $required */
        $required = $schema->required;

        if (\is_array($properties)) {
            $schema->properties = array_values(array_filter(
                $properties,
                static fn (Property $property): bool => 'id' !== $property->property,
            ));
        }

        if (\is_array($required)) {
            $schema->required = array_values(array_diff($required, ['id']));
        }
    }
}
