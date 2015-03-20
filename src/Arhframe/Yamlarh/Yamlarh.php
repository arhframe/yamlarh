<?php
namespace Arhframe\Yamlarh;

use Arhframe\Util\File;
use Arhframe\Yamlarh\YamlarhNode\AbstractYamlarhNode;
use Arhframe\Yamlarh\YamlarhNode\IncludeYamlarhNode;

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
class Yamlarh
{
    /**
     * @var array
     */
    private $arrayToReturn = array();
    /**
     * @var
     */
    private $fileName;
    /**
     * @var string
     */
    private $paramaterKey = "yar-";
    /**
     *
     */
    const IMPORT_KEY = "import";
    /**
     * @var array
     */
    private $accessibleVariable = array();

    /**
     * @var AbstractYamlarhNode[]
     */
    private $nodes;

    /**
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->fileName = $filename;
        $this->addNode("include", new IncludeYamlarhNode());

    }

    /**
     * @return array
     */
    public function parse()
    {
        $this->arrayToReturn = array();
        $this->parseFile(new File($this->fileName));
        $this->browseVar($this->arrayToReturn);
        return $this->arrayToReturn;
    }

    /**
     * @param $arrayToReturn
     * @param null $completeArray
     */
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

    /**
     * @param $value
     * @param $arrayToReturn
     * @param $completeArray
     * @return array|mixed|object|string
     */
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
            return $this->insertObject($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return object
     */
    public function insertObject($value)
    {
        $value = trim(substr($value, 2));
        preg_match('#\((.*)\)$#', $value, $matchesModule);
        $args = null;
        $value = preg_replace('#\((.*)\)$#', '', $value);
        if (!empty($matchesModule[1])) {
            $args = explode(',', $matchesModule[1]);
            array_walk($args, function (&$value) {
                $value = trim($value);
            });
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

    /**
     * @param $value
     * @param $arrayToReturn
     * @param $completeArray
     * @return array|mixed
     */
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
                    if (!isset($finalVar[$var])) {
                        $finalVar = null;
                        break;
                    }
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

    /**
     * @param $value
     * @param $arrayToReturn
     * @return mixed
     */
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

    /**
     * @param $var
     * @param $values
     * @return mixed
     */
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

    /**
     * @param File $file
     */
    private function parseFile(File $file)
    {
        $parsedYml = FileLoader::loadFile($file);

        if (empty($parsedYml)) {
            return;
        }
        $this->arrayToReturn = $this->arrayMergeRecursiveDistinct($this->arrayToReturn, $parsedYml);
        foreach ($this->arrayToReturn as $key => $value) {
            if ($key != $this->paramaterKey . Yamlarh::IMPORT_KEY) {
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
        foreach ($this->nodes as $node) {
            $this->arrayToReturn = $node->run();
        }
    }

    /**
     * @param $fileName
     * @param File $file
     * @throws \Exception
     */
    private function getFromImport($fileName, File $file)
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
            throw new \Exception("The yml file " . $file->absolute() . " can't found yml file " . $fileName . " for import");
        }
        $this->parseFile(new File($fileFinalName));

    }

    /**
     * @return mixed
     */
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

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = $this->arrayMergeRecursiveDistinct($merged [$key], $value);
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

    /**
     * @param $key
     * @param $var
     */
    public function addAccessibleVariable($key, $var)
    {
        $this->accessibleVariable[$key] = $var;
    }

    /**
     * @param $key
     */
    public function removeAccessibleVariable($key)
    {
        unset($this->accessibleVariable[$key]);
    }

    /**
     * @return array
     */
    public function getArrayToReturn()
    {
        return $this->arrayToReturn;
    }

    /**
     * @param array $arrayToReturn
     */
    public function setArrayToReturn($arrayToReturn)
    {
        $this->arrayToReturn = $arrayToReturn;
    }

    /**
     * @return string
     */
    public function getParamaterKey()
    {
        return $this->paramaterKey;
    }

    /**
     * @param string $paramaterKey
     */
    public function setParamaterKey($paramaterKey)
    {
        if (empty($paramaterKey)) {
            return;
        }
        if (preg_match("#" . preg_quote(":/\\") . "#i", $paramaterKey)) {
            throw new \Exception(sprintf("Yamlarh error: invalid parameter key '%s' must not contains ':', '/' or '\\'", $paramaterKey));
        }
        if ($paramaterKey[strlen($paramaterKey) - 1] != '-') {
            $paramaterKey = $paramaterKey . '-';
        }
        $this->paramaterKey = $paramaterKey;
    }

    /**
     * @return YamlarhNode\AbstractYamlarhNode[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param YamlarhNode\AbstractYamlarhNode[] $nodes
     */
    public function setNodes($nodes)
    {
        foreach ($nodes as $nodeName => $node) {
            $this->addNode($nodeName, $node);
        }
    }

    public function addNode($nodeName, AbstractYamlarhNode $node)
    {
        $this->nodes[$nodeName] = $node;
        $node->setNodeName($nodeName);
        $node->setYamlarh($this);
    }

    public function deleteNode($nodeName)
    {
        unset($this->nodes[$nodeName]);
    }
}
