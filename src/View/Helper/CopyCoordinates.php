<?php
namespace Mapping\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class CopyCoordinates extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        return implode('', [
            $view->formSelect($element->getCoordinatesPropertyElement()),
            $view->formSelect($element->getCoordinatesOrderElement()),
            $view->formSelect($element->getCoordinatesDelimiterElement()),
        ]);
    }
}
