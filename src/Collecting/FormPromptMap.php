<?php
namespace Mapping\Collecting;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormPromptMap extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $value = $element->getValue();
        $lat = isset($value['lat']) ? $value['lat'] : '';
        $lng = isset($value['lng']) ? $value['lng'] : '';
        return sprintf('
            <input type="hidden" class="collecting-map-lat" name="%1$s[lat]" value="%2$s">
            <input type="hidden" class="collecting-map-lng" name="%1$s[lng]" value="%3$s">
            <div class="collecting-map" style="height:300px;"></div>',
            $element->getName(),
            $this->getView()->escapeHtml($lat),
            $this->getView()->escapeHtml($lng)
        );
    }
}
