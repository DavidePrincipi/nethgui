<?php
namespace Nethgui\Core;

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
 * Invoke a Nethgui javascript method on the client-side.
 *
 * Applies Command pattern
 *
 * Roles:
 * - Client, a Module
 * - Invoker, a Renderer
 * - Receiver, a Widget or the client-side javascript components.
 *
 * @see http://en.wikipedia.org/wiki/Command_pattern
 */
interface CommandInterface
{

    /**
     * Executes the command on the given receiver object
     *
     * Called by Invoker
     *
     * @see setReceiver()
     * @param object $context
     * @return mixed.
     */
    public function execute();

    /**
     * Set the command receiver, the object where the command is executed
     *
     * @param object An object implementing either CommandReceiverInterface or \Nethgui\Client\CommandReceiverAggregateInterface
     * @see CommandReceiverInterface
     * @see \Nethgui\Client\CommandReceiverAggregateInterface
     * @return CommandInterface
     */
    public function setReceiver($receiver);

    /**
     * @see execute()
     * @return boolean
     */
    public function isExecuted();
}
