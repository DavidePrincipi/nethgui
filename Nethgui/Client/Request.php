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
 * @todo describe class
 *
 */
class Request implements \Nethgui\Core\RequestInterface
{
    const CONTENT_TYPE_JSON = 1;
    const CONTENT_TYPE_HTML = 2;

    /**
     * @var array
     */
    private $data;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var bool
     */
    private $submitted;

    /**
     * @see \Nethgui\Core\RequestInterface::getArguments()
     * @var array
     */
    private $arguments;
    private $attributes;

    public function __construct(UserInterface $user, $data, $submitted, $arguments, $attributes)
    {
        if (is_null($data)) {
            $data = array();
        }
        if ( ! is_array($data)) {
            $data = array($data);
        }
        $this->user = $user;
        $this->data = $data;
        $this->submitted = (bool) $submitted;
        $this->arguments = $arguments;
        $this->attributes = $attributes;
    }

    public function hasParameter($parameterName)
    {
        return array_key_exists($parameterName, $this->data);
    }

    public function isEmpty()
    {
        return empty($this->data);
    }

    public function isSubmitted()
    {
        return $this->submitted;
    }

    public function getParameters()
    {
        return array_keys($this->data);
    }

    public function getParameter($parameterName)
    {
        if ( ! isset($this->data[$parameterName])) {
            return NULL;
        }
        return $this->data[$parameterName];
    }

    public function getParameterAsInnerRequest($parameterName, $arguments = array())
    {
        return new self($this->user, $this->getParameter($parameterName), $this->submitted, $arguments, array());
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getAttribute($name)
    {
        if ( ! isset($this->attributes[$name])) {
            return NULL;
        }

        return $this->attributes[$name];
    }

    public function getContentType()
    {
        return $this->getAttribute('CONTENT_TYPE');
    }

    public function isXmlHttpRequest()
    {
        return $this->getAttribute('XML_HTTP_REQUEST');
    }

}
