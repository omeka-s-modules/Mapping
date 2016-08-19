<?php
namespace Mapping\Collecting;

use Collecting\Form\Element\PromptHtml;
use Collecting\Form\Element\PromptIsRequiredTrait;
use Zend\InputFilter\InputProviderInterface;

class PromptMap extends PromptHtml implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification()
    {
        return [
            'validators' => [
                [
                    'name' => 'Callback',
                    'options' => [
                        'callback' => [$this, 'isValid'],
                        'messages' => [
                            'callbackValue' => 'You must select a location on the map.', // @translate
                        ],
                    ],
                ],
            ],
        ];
    }

    public function isValid($value)
    {
        if (!$this->required) {
            return true;
        }
        if (is_numeric($value['lat']) && is_numeric($value['lat'])) {
            return true;
        }
        return false;
    }
}
