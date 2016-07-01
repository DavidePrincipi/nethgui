<?php
namespace Nethgui\Widget\Xhtml;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Slider UI widget.
 * 
 * Select a value inside a given range
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Slider extends \Nethgui\Widget\XhtmlWidget
{

    protected function getJsWidgetTypes()
    {
        return array_merge(array('Nethgui:inputcontrol'), parent::getJsWidgetTypes());
    }

    protected function renderContent()
    {
        $flags = $this->applyDefaultLabelAlignment($this->getAttribute('flags'), \Nethgui\Renderer\WidgetFactoryInterface::LABEL_RIGHT);

        if ($flags & \Nethgui\Renderer\WidgetFactoryInterface::SLIDER_ENUMERATIVE) {
            return $this->renderEnumerative();
        }
        return $this->renderRange();
    }

    private function renderEnumerative()
    {
        $flags = $this->getAttribute('flags');
        $name = $this->getAttribute('name');
        $value = $this->getAttribute('value', $this->view[$name]);
        $label = $this->getAttribute('label', '${0}');
        $cssClass = trim('Slider Enumerative ' . $this->getAttribute('class', ''));

        $choices = $this->getChoices($name, $dataSourceName);
        $tagContent = $this->optGroups($value, $choices);
        return $this->labeledControlTag($label, 'select', $name, $flags, $cssClass, array(), $tagContent);
    }

    private function renderRange()
    {
        $flags = $this->getAttribute('flags');
        $name = $this->getAttribute('name');
        $value = $this->getAttribute('value', $this->view[$name]);
        $label = $this->getAttribute('label', $this->getTranslateClosure($name . '_label'));
        $cssClass = trim('Slider Range ' . $this->getAttribute('class', ''));

        $range = array(
            'min' => floatval($this->getAttribute('min', 0)),
            'max' => floatval($this->getAttribute('max', 100)),
            'step' => floatval($this->getAttribute('step', 1)),
        );

        $attributes = array(
            'value' => strval($value),
            'type' => 'text',
            'data-settings' => json_encode($range),
        );

        return $this->labeledControlTag($label, 'input', $name, $flags, $cssClass, $attributes);
    }

}
