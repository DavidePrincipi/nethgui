<?php
namespace Nethgui\Module;

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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Resource extends \Nethgui\Core\Module\Standard implements \Nethgui\Core\CommandReceiverInterface, \Nethgui\Core\GlobalFunctionConsumerInterface
{

    private $code;
    private $useList;
    private $fileName;

    /**
     *
     * @var \Nethgui\Core\GlobalFunctionWrapper
     */
    private $php;

    public function __construct()
    {
        parent::__construct(NULL);
        $this->code = array();
        $this->useList = array();
        $this->php = new \Nethgui\Core\GlobalFunctionWrapper;
    }

    public function bind(\Nethgui\Core\RequestInterface $request)
    {
        parent::bind($request);
        $fileName = implode('/', $request->getArguments());

        if ( ! $fileName) {
            $this->fileName = FALSE;
            return;
        }

        $this->fileName = $fileName . '.' . $request->getExtension();

        if ( ! $this->php->file_exists($this->getCachePath($this->fileName))) {
            throw new \Nethgui\Exception\HttpException('Not Found', 404, 1324373071);
        }
    }

    public function prepareViewXhtml(\Nethgui\Core\ViewInterface $view)
    {
        $fragments = array(
            'js' => '<script src="%URI"></script>',
            'css' => '<link rel="stylesheet" type="text/css" href="%URI" />'
        );

        foreach (array_keys($fragments) as $ext) {
            $view[$ext] = $view->spawnView($this);
            $thisModule = $this;

            $templateClosure = function(\Nethgui\Renderer\AbstractRenderer $renderer)
                use ($ext, $thisModule, $fragments)
                {
                    $command = $renderer
                        ->getCommandListFor($ext)
                        ->setReceiver($thisModule)
                        ->execute()
                    ;

                    $uriList = $thisModule->getUseList($ext);
                    $cachedFile = $thisModule->getFileName($ext);
                    if ($cachedFile !== FALSE) {
                        $uriList[] = $renderer->getModuleUrl('/Resource/' . $cachedFile);
                    }
                    $output = '';

                    foreach ($uriList as $uri) {
                        $output .= strtr($fragments[$ext], array('%URI' => $uri));
                    }
                    return $output;
                };

            $view[$ext]->setTemplate($templateClosure);
        }

        $view->setTemplate(FALSE);
    }

    public function prepareView(\Nethgui\Core\ViewInterface $view, $mode)
    {
        parent::prepareView($view, $mode);

        if ($view->getTargetFormat() == 'xhtml') {
            $this->prepareViewXhtml($view);
        } elseif ($this->fileName) {
            $view->setTemplate(array($this, 'renderFileContent'));
        }
    }

    protected function calcFileName($ext)
    {
        if ( ! isset($this->code[$ext])) {
            return FALSE;
        }
        $fileName = substr(md5(serialize($this->code[$ext])), 0, 8) . '.' . $ext;
        return $fileName;
    }

    protected function getCachePath($fileName = '')
    {
        return __DIR__ . '/../Cache/' . $fileName;
    }

    protected function cacheWrite($fileName, $ext)
    {
        $resource = $this->php->fopen($this->getCachePath($fileName), 'w');

        if ($resource === FALSE) {
            $error = error_get_last();
            if ( ! is_null($error)) {
                $message = $error['message'];
            } else {
                $message = '';
            }

            throw new \UnexpectedValueException(sprintf('%s: cannot open a file in Cache directory - %s', get_class($this), $message), 1324393391);
        }

        foreach ($this->code[$ext] as $part) {

            if ($part['file']) {
                $data = $this->php->file_get_contents($part['file']);
            } else {
                $data = $part['data'];
            }

            $this->php->fwrite($resource, $data);
        }

        $this->php->fclose($resource);
    }

    protected function includeFile($fileName)
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if ( ! isset($this->code[$ext])) {
            $this->code[$ext] = array();
        }

        $this->code[$ext][$fileName] = array(
            'file' => $fileName,
            'tstamp' => 0,
            'data' => FALSE
        );
    }

    protected function appendCode($code, $ext)
    {
        if ( ! isset($this->code[$ext])) {
            $this->code[$ext] = array();
        }
        $this->code[$ext][] = array(
            'file' => FALSE,
            'tstamp' => 0,
            'data' => $code
        );
    }

    public function getFileName($ext)
    {
        static $fileNames = array();

        if ( ! isset($fileNames[$ext])) {
            $fileNames[$ext] = $this->calcFileName($ext);

            if ($fileNames[$ext] !== FALSE && ! file_exists($fileNames[$ext])) {
                $this->cacheWrite($fileNames[$ext], $ext);
            }
        }

        return $fileNames[$ext];
    }

    public function getUseList($ext)
    {
        return array_filter($this->useList, function($uri) use ($ext) {
            return $ext === pathinfo($uri, PATHINFO_EXTENSION);
        });
    }

    public function renderFileContent(\Nethgui\Renderer\AbstractRenderer $renderer)
    {
        return $this->php->file_get_contents($this->getCachePath($this->fileName));
    }

    public function executeCommand(\Nethgui\Core\ViewInterface $origin, $selector, $name, $arguments)
    {
        if ($name === 'includeFile') {
            $this->includeFile($arguments[0]);
        } elseif ($name === 'appendCode') {
            $this->appendCode($arguments[0], $arguments[1]);
        } elseif ($name === 'useFile' && isset($arguments[0])) {
            $this->useList[] = $origin->getPathUrl() . '/' . $arguments[0];
        }
    }

    public function setGlobalFunctionWrapper(\Nethgui\Core\GlobalFunctionWrapper $object)
    {
        $this->php = $object;
    }

}