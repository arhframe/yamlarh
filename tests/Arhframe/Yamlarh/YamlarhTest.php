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


class YamlarhTest extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        global $var3;
        $expected = array("arhframe" => array(
            "myvar1" => "test",
            "myvar2" => "test",
            "myvar3" => "testvar",
            "myvar4" => "testconstant",
            "myvar5" => "var added",
        ));
        $var3 = 'testvar';
        define('VARCONSTANT', 'testconstant');

        $yamlarh = new Yamlarh(__DIR__ . '/resource/testinject.yaml');
        $yamlarh->addAccessibleVariable("addedInYamlarh", "var added");
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);

        $yamlarh->setFileName(__DIR__ . '/resource/testinject.json');
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);

        $yamlarh->setFileName(__DIR__ . '/resource/testinject.xml');
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);
    }

    public function testImport()
    {
        $expected = array(
            "arhframe" => array("var1" => "varoverride"),
            "test" => "arhframe",
            "test2" => "var3"
        );
        $yamlarh = new Yamlarh(__DIR__ . '/resource/import/file1.xml');
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);
    }

    public function testInclude()
    {
        $expected = array(
            "arhframe" => array("var1" => "var"),
            "test" => array("test2" => "var3")
        );
        $yamlarh = new Yamlarh(__DIR__ . '/resource/include/file1.xml');
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);
    }
}
