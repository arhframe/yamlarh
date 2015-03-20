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


use Arhframe\Util\File;
use Arhframe\Yamlarh\Yamlarh;

class IncludeYamlarhNode extends AbstractYamlarhNode
{

    public function run()
    {
        return $this->searchForInclude();
    }

    private function searchForInclude(&$arrayYaml = null)
    {
        if (empty($arrayYaml)) {
            $arrayYaml = $this->yamlarh->getArrayToReturn();
        }
        $includeYaml = null;
        foreach ($arrayYaml as $key => $value) {
            if (is_array($value) && $key !== $this->yamlarh->getParamaterKey() . $this->nodeName && count($value) > 0) {
                $includeYaml[$key] = $this->searchForInclude($value);
                continue;
            }
            if ($key !== $this->yamlarh->getParamaterKey() . $this->nodeName) {
                $includeYaml[$key] = $value;
                continue;
            }
            if (!is_array($value)) {
                $value = array($value);
            }
            $includeYaml = array();
            foreach ($value as $includeFile) {
                $currentFile = new File($this->yamlarh->getFilename());
                $includeFile = new File($includeFile);
                if (!$includeFile->isFile()) {
                    $includeFile->setFolder($currentFile->getFolder() . $includeFile->getFolder());
                }
                $yamlArh = new Yamlarh($includeFile->absolute());
                $includeYaml = array_merge($yamlArh->parse(), $arrayYaml, $includeYaml);
            }

            unset($includeYaml[$this->yamlarh->getParamaterKey() . $this->nodeName]);
        }
        return $includeYaml;
    }
}
