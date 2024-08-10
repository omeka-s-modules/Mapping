<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;
use Omeka\Form\Element\Query;

class QueryFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => Query::class,
            'name' => 'o:block[__blockIndex__][o:data][query]',
            'options' => [
                'label' => 'Query', // @translate
                'info' => 'Attach items using this query. No query means all items.', // @translate
            ],
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'query' => '',
        ];

        if (isset($rawData['query']) && is_string($rawData['query'])) {
            $data['query'] = $rawData['query'];
        }

        return $data;
    }
}
