<?php
namespace Arhframe\Yamlarh;

use Arhframe\Util\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Yaml wrapper to permit override from module
 * allow import other yaml file by passing @import:
 *                                             - /path/of/your/file
 * allow to pass value directly from key in yaml with %keyYaml%
 * but also variable from scope or constant like this %YOURCONSTANT%
 *  and allow you to inject object in yaml like this: !! your.class(your, parameter)
 *
 * import: @import
 * key variable from yaml file: %keyYaml%
 * variable from scope ($test in scope): %test%
 * constant from scope (define("CONSTANTTEST", "testr")): %CONSTANTTEST%
 * object: !! your.class(your, parameter)
 *
 */
function trim_value(&$value)
{
    $value = trim($value);
}

class Yamlarh
{
    private $arrayToReturn = array();
    private $fileName;
    /**
     * @var array
     */
    private $accessibleVariable = array();

    public function __construct($filename)
    {
        $this->fileName = $filename;
    }

    public function parse()
    {
        $this->parseFile(new File($this->fileName));
        $this->browseVar($this->arrayToReturn);
        return $this->arrayToReturn;
    }

    public function browseVar(&$arrayToReturn, $completeArray = null)
    {
        if (empty($completeArray)) {
            $completeArray = $arrayToReturn;
        }
        foreach ($arrayToReturn as $key => &$value) {
            if (is_array($value)) {
                $this->browseVar($value, $completeArray);
            } else {
                $arrayToReturn[$key] = $this->inject($value, $arrayToReturn, $completeArray);
            }

        }
    }

    public function inject($value, &$arrayToReturn, $completeArray)
    {
        if (!is_string($value)) {
            return $value;
        }
        $value = trim($value);
        if (preg_match('#%([^%]*)%#', $value)) {
            return $this->insertVar($value, $arrayToReturn, $completeArray);
        }
        if ($value[0] == "!" && $value[1] == "!") {
            return $this->insertObject($value, $arrayToReturn);
        }

        return $value;
    }

    public function insertObject($value, $arrayToReturn)
    {
        $value = trim(substr($value, 2));
        preg_match('#\((.*)\)$#', $value, $matchesModule);
        $args = null;
        $value = preg_replace('#\((.*)\)$#', '', $value);
        if (!empty($matchesModule[1])) {
            $args = explode(',', $matchesModule[1]);
            array_walk($args, 'Arhframe\Yamlarh\trim_value');
        }
        $value = str_replace('/', '.', $value);
        $value = str_replace('.', '\\', $value);
        $object = new \ReflectionClass($value);
        if (!empty($args)) {
            return $object->newInstanceArgs($args);
        } else {
            return $object->newInstance();
        }

    }

    public function insertVar($value, &$arrayToReturn, $completeArray)
    {

        $value = preg_replace('#%s%#', '%s%%', $value);
        $value = preg_replace('#%s %#', '%s% %', $value);
        preg_match_all('#%([^%]*)%#', $value, $matchesVar);
        $matchesVar = $matchesVar[1];
        $startValue = $value;
        foreach ($matchesVar as $value) {
            if ($value == "s" || ($value[0] == "s" && $value[1] == " ")) {
                $startValue = preg_replace('#%' . preg_quote($value) . '%#', '%s', $startValue);
                continue;
            }
            $varArray = explode('.', $value);
            if (count($varArray) > 1) {
                $finalVar = $completeArray;
                foreach ($varArray as $var) {
                    $finalVar = $finalVar[$var];
                }
                if (empty($finalVar)) {
                    $finalVar = $this->getVar($varArray, $arrayToReturn);
                }
                $startValue = preg_replace('#%' . preg_quote($value) . '%#', $finalVar, $startValue);

                continue;
            }
            $var = $this->getVar($value, $arrayToReturn);
            if (is_object($var) || is_array($var)) {
                $startValue = $var;
            } else {
                $startValue = preg_replace('#%' . preg_quote($value) . '%#', $var, $startValue);
            }

        }

        return $startValue;
    }

    private function getVar($value, &$arrayToReturn)
    {
        $allValues = null;
        if (is_array($value)) {
            $allValues = $value;
            $value = $value[0];
        }
        $var = $arrayToReturn[$value];
        global $$value;
        $varFromFile = $$value;
        if (!empty($varFromFile)) {
            $var = $varFromFile;
        }
        if (defined($value)) {
            $var = constant($value);
        }
        if (!empty($this->accessibleVariable[$value])) {
            $var = $this->accessibleVariable[$value];
        }

        $var = $this->getComplexeVar($var, $allValues);
        return $var;
    }

    private function getComplexeVar($var, $values)
    {
        if (empty($values)) {
            return $var;
        }
        $values = array_slice($values, 1);
        if (is_object($var) && !empty($values)) {
            $get = "get" . ucfirst($values[0]);
            return $this->getComplexeVar($var->$get(), $values);
        }
        if (is_array($var) && !empty($values)) {
            return $this->getComplexeVar($var[$values[0]], $values);
        }
        return $var;
    }

    private function parseFile($file)
    {
        $parsedYml = Yaml::parse($file->getContent());

        if (empty($parsedYml)) {
            return;
        }
        $this->arrayToReturn = $this->array_merge_recursive_distinct($this->arrayToReturn, $parsedYml);
        foreach ($this->arrayToReturn as $key => $value) {
            if ($key != "@import") {
                continue;
            }
            unset($this->arrayToReturn[$key]);
            if (!is_array($value)) {
                $this->getFromImport($value, $file);
            } else {
                foreach ($value as $fileName) {
                    $this->getFromImport($fileName, $file);
                }
            }
        }
        $this->arrayToReturn = $this->searchForInclude();
    }

    private function getFromImport($fileName, $file)
    {
        if (is_file($fileName)) {
            $fileFinalName = $fileName;
        } else {
            $fileFinalName = $file->getFolder() . '/' . $fileName;
        }
        $fileTmp = new File($fileFinalName);
        if (!$fileTmp->isFile()) {
            $fileFinalName = $file->getFolder() . '/' . $fileName;
        }
        if (!is_file($fileFinalName)) {
            throw new YamlarhException("The yml file " . $file->absolute() . " can't found yml file " . $fileName . " for import");
        }
        $this->parseFile(new File($fileFinalName));

    }

    private function searchForInclude(&$arrayYaml = null)
    {
        if (empty($arrayYaml)) {
            $arrayYaml = $this->arrayToReturn;
        }
        $includeYaml = null;
        foreach ($arrayYaml as $key => $value) {
            if (is_array($value) && $key !== '@include' && count($value) > 0) {
                $includeYaml[$key] = $this->searchForInclude($value);
                continue;
            }
            if ($key !== '@include') {
                $includeYaml[$key] = $value;
                continue;
            }
            if (!is_array($value)) {
                $value = array($value);
            }
            $includeYaml = array();
            foreach ($value as $includeFile) {
                $yamlArh = new Yamlarh($includeFile);
                $includeYaml = array_merge($yamlArh->parse(), $arrayYaml, $includeYaml);
            }

            unset($includeYaml['@include']);
        }
        return $includeYaml;
    }

    public function dump($array)
    {
        return Yaml::dump($array);
    }

    public function getFilename()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = $this->array_merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @return array
     */
    public function getAccessibleVariable()
    {
        return $this->accessibleVariable;
    }

    /**
     * @param array $accessibleVariable
     */
    public function setAccessibleVariable(array $accessibleVariable)
    {
        $this->accessibleVariable = $accessibleVariable;
    }

    public function addAccessibleVariable($key, $var)
    {
        $this->accessibleVariable[$key] = $var;
    }

    public function removeAccessibleVariable($key)
    {
        unset($this->accessibleVariable[$key]);
    }

}
