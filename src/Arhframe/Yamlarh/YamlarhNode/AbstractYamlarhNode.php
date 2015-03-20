<?php
/**
 * Copyright (C) 2014 Arthur Halet
 *
 * This software is distributed under the terms and conditions of the 'MIT'
 * license which can be found in the file 'LICENSE' in this package distribution
 * or at 'http://opensource.org/licenses/MIT'.
 *
 * Author: Arthur Halet
 * Date: 17/03/2015
 */

namespace Arhframe\Yamlarh\YamlarhNode;


use Arhframe\Yamlarh\Yamlarh;

abstract class AbstractYamlarhNode
{
    /**
     * @var Yamlarh
     */
    protected $yamlarh;
    protected $nodeName;

    public function __construct(Yamlarh $yamlarh = null, $nodeName = null)
    {
        $this->yamlarh = $yamlarh;
        $this->nodeName = $nodeName;
    }

    /**
     * @return mixed
     */
    public function getYamlarh()
    {
        return $this->yamlarh;
    }

    /**
     * @param mixed $yamlarh
     */
    public function setYamlarh(Yamlarh $yamlarh)
    {
        $this->yamlarh = $yamlarh;
    }

    /**
     * @return mixed
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * @param mixed $nodeName
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;
    }

    abstract public function run();

}
