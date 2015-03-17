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


use Arhframe\Yamlarh\Fake\Foo;
use Arhframe\Yamlarh\YamlarhNode\IncludeYamlarhNode;

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

    public function testObject()
    {
        $yamlarh = new Yamlarh(__DIR__ . '/resource/testobject.yml');
        $yamlarh->addAccessibleVariable('foo', new Foo("nothing"));
        $array = $yamlarh->parse();
        $this->assertInstanceOf("Arhframe\\Yamlarh\\Fake\\Foo", $array['test']);
        $this->assertEquals("data", $array["foo"]);
    }

    public function testNode()
    {
        $yamlarh = new Yamlarh(__DIR__ . '/resource/testobject.yml');
        $this->assertArrayHasKey("include", $yamlarh->getNodes());
        $yamlarh->deleteNode("include");
        $this->assertArrayNotHasKey("include", $yamlarh->getNodes());

        $yamlarh->setNodes(array("include" => new IncludeYamlarhNode()));
        $this->assertArrayHasKey("include", $yamlarh->getNodes());

    }

    public function testNewParameter()
    {
        $expected = array(
            "arhframe" => array("var1" => "varoverride"),
            "test" => "arhframe",
            "test2" => "var3"
        );
        $yamlarh = new Yamlarh(__DIR__ . '/resource/newparameter/file1.xml');
        $yamlarh->setParamaterKey("sp");
        $array = $yamlarh->parse();
        $this->assertEquals($expected, $array);
    }

}
