<?php
namespace Nethgui\Client;

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
class Command implements \Nethgui\Core\CommandInterface
{

    /**
     *
     * @var object
     */
    private $receiver;

    /**
     *
     * @var string
     */
    private $methodName;

    /**
     *
     * @var array
     */
    private $arguments;

    /**
     *
     * @var boolean
     */
    private $executed;

    public function __construct($methodName, $arguments = array())
    {
        $this->methodName = $methodName;
        $this->arguments = $arguments;
        $this->executed = FALSE;
    }

    public function execute()
    {
        if ($this->executed === TRUE) {
            throw new LogicException(sprintf('%s: command was already executed', get_class($this)));
        }

        if ($this->receiver instanceof \Nethgui\Core\CommandReceiverInterface) {
            $this->executed = TRUE;
            return $this->receiver->executeCommand($this->methodName, $this->arguments);
        }

        if ($this->receiver instanceof \Nethgui\Core\CommandReceiverAggregateInterface) {
            $this->executed = TRUE;
            return $this->receiver->getCommandReceiver()->executeCommand($this->methodName, $this->arguments);
        }

        throw new LogicException(sprintf('%s: invalid receiver object', get_class($this)));
    }

    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function isExecuted()
    {
        return $this->executed === TRUE;
    }

}