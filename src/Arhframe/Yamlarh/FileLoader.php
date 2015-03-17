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

namespace Arhframe\Yamlarh;


use Arhframe\Util\File;
use Symfony\Component\Yaml\Yaml;

class FileLoader
{
    public static $extJson = array("json");
    public static $extYaml = array("yml", "yaml");
    public static $extXml = array("xml");

    public static function loadFile(File $file)
    {
        $fileExt = strtolower($file->getExtension());
        if (in_array($fileExt, self::$extJson)) {
            return self::loadFileJson($file);
        }
        if (in_array($fileExt, self::$extYaml)) {
            return self::loadFileYml($file);
        }
        if (in_array($fileExt, self::$extXml)) {
            return self::loadFileXml($file);
        }
        return null;
    }

    private static function loadFileYml(File $file)
    {
        return Yaml::parse($file->getContent());
    }

    private static function loadFileXml(File $file)
    {
        libxml_use_internal_errors(true);
        $data = simplexml_load_file($file->absolute(), null, LIBXML_NOERROR);
        if ($data === false) {
            $errors = libxml_get_errors();
            $latestError = array_pop($errors);
            throw new \Exception($latestError->message, $latestError->code);
        }
        return json_decode(json_encode($data), true);
    }

    private static function loadFileJson(File $file)
    {
        return json_decode($file->getContent(), true);
    }
}
