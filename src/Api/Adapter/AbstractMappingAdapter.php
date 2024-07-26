<?php
namespace Mapping\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

abstract class AbstractMappingAdapter extends AbstractEntityAdapter
{
    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if ($this->shouldHydrate($request, 'o-module-mapping:bounds')) {
            $entity->setBounds($request->getValue('o-module-mapping:bounds'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $bounds = $entity->getBounds();
        if (null !== $bounds && 4 !== count(array_filter(explode(',', $bounds), 'is_numeric'))) {
            $errorStore->addError('o-module-mapping:bounds', 'Map bounds must contain four numbers separated by commas'); // @translate
        }
    }
}
