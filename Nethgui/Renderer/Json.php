<?php
namespace Nethgui\Renderer;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
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
 * @ignore
 */
class Json extends AbstractRenderer
{

    private function deepWalk(&$events, &$commands)
    {
        foreach ($this as $offset => $value) {

            $eventTarget = $this->getClientEventTarget($offset);
            if ($value instanceof \Nethgui\Core\ViewInterface) {
                if ( ! $value instanceof Json) {
                    $value = new Json($value);
                }
                $value->deepWalk($events, $commands);
                continue;
            } elseif ($value instanceof \Nethgui\Core\CommandInterface) {
                $commands[] = $value->setReceiver(new JsonReceiver($this->view, $offset))->execute();
                continue;
            } elseif ($value instanceof \Traversable) {
                $eventData = $this->traversableToArray($value);
            } else {
                $eventData = $value;
            }

            $events[] = array($eventTarget, $eventData);
        }
    }

    /**
     * Convert a \Traversable object to a PHP array
     * @param \Traversable $value
     * @return array
     */
    private function traversableToArray(\Traversable $value)
    {
        $a = array();
        foreach ($value as $k => $v) {
            if ($v instanceof \Traversable) {
                $v = $this->traversableToArray($v);
            }
            $a[$k] = $v;
        }
        return $a;
    }

    protected function render()
    {
        $events = array();
        $commands = array();

        $this->deepWalk($events, $commands);
        if (count($commands) > 0) {
            $events[] = array('ClientCommandHandler', $commands);
        }

        return json_encode($events);
    }

}

/**
 * @ignore
 */
class JsonReceiver implements \Nethgui\Core\CommandReceiverInterface
{

    private $offset;

    /**
     *
     * @var \Nethgui\Core\ViewInterface
     */
    private $view;

    public function __construct(\Nethgui\Core\ViewInterface $view, $offset)
    {
        $this->view = $view;
        $this->offset = $offset;
    }

    public function executeCommand($name, $arguments)
    {
        if ($name == 'delay'
            && $arguments[0] instanceof \Nethgui\Core\CommandInterface) {
            $receiver = '';
            // replace the first argument with the array equivalent
            $arguments[0] = $arguments[0]->setReceiver(clone $this)->execute();
        } elseif ($name == 'redirect' || $name == 'queryUrl') {
            $receiver = '';
            $arguments[0] = $this->view->getModuleUrl($arguments[0]);
        } elseif ($name == 'activateAction') {
            $receiver = '';

            $tmp = array(
                $this->view->getUniqueId($arguments[0]),
                $this->view->getModuleUrl($arguments[0]),
                $this->view->getUniqueId()
            );

            if (isset($arguments[1])) {
                $tmp[1] = $this->view->getModuleUrl($arguments[1]);
            }

            if (isset($arguments[2])) {
                $tmp[2] = $this->view->getUniqueId($arguments[2]);
            }

            $arguments = $tmp;
        } elseif ($name == 'debug' || $name == 'alert') {
            $receiver = '';
        } else {
            $receiver = is_numeric($this->offset) ? '#' . $this->view->getUniqueId() : '.' . $this->view->getClientEventTarget($this->offset);
        }

        return $this->commandForClient($receiver, $name, $arguments);
    }

    private function commandForClient($receiver, $name, $arguments)
    {
        return array(
            'receiver' => $receiver,
            'methodName' => $name,
            'arguments' => $arguments,
        );
    }

}