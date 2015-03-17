Yamlarh
=======
[![Build Status](https://travis-ci.org/arhframe/yamlarh.svg)](https://travis-ci.org/arhframe/yamlarh) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/arhframe/yamlarh/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/arhframe/yamlarh/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/arhframe/yamlarh/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/arhframe/yamlarh/?branch=master)

Yamlarh is now just a name, with this tool you can inject value or date in a formatted file (as `json`, `xml` or `yaml`).

You can inject into your formatted file:
  * object
  * constant from scope 
  * Variable from global scope
  * Variable from formatted file

You can also import other formatted file inside a formatted file file for overriding

Installation
=======

Through Composer, obviously:

```json
{
    "require": {
        "arhframe/yamlarh": "1.*"
    }
}
```

Usage
========

```php
use Arhframe\Yamlarh\Yamlarh;

$yamlarh = new Yamlarh(__DIR__.'/path/to/formatted/file');
$array = $yamlarh->parse();
```

Exemple
=========

Variable injection
---------

Variable injection is hierarchical, it will find in this order:
  1. In the yaml file with import
  2. In your global scope
  3. In your constants
  4. In accessible variables set in yamlarh

Yaml file:
```yml
arhframe:
  myvar1: test
  myvar2: %arhframe.myvar1%
  myvar3: %var3%
  myvar4: %VARCONSTANT%
  myvar5: %addedInYamlarh%
```

Or in xml:
```xml
<?xml version="1.0" encoding="UTF-8" ?>
<yamlarh>
    <arhframe>
        <myvar1>test</myvar1>
        <myvar2>%arhframe.myvar1%</myvar2>
        <myvar3>%var3%</myvar3>
        <myvar4>%VARCONSTANT%</myvar4>
        <myvar5>%addedInYamlarh%</myvar5>
    </arhframe>
</yamlarh>
```

Or in json:
```json
{
  "arhframe": {
    "myvar1": "test",
    "myvar2": "%arhframe.myvar1%",
    "myvar3": "%var3%",
    "myvar4": "%VARCONSTANT%",
    "myvar5": "%addedInYamlarh%"
  }
}
```

Php file:
```php
use Arhframe\Yamlarh\Yamlarh;
$var3 = 'testvar';
define('VARCONSTANT', 'testconstant');
$yamlarh = new Yamlarh(__DIR__.'/test.yml');
$yamlarh->addAccessibleVariable("addedInYamlarh", "var added");
$array = $yamlarh->parse();
echo print_r($array);
```

Output:
```
  Array
  (
      [arhframe] => Array
          (
              [myvar1] => test
              [myvar2] => test
              [myvar3] => testvar
              [myvar4] => testconstant
              [myvar5] => var added
          )
  )
```

Object injection
---------
It use [snakeyml](https://code.google.com/p/snakeyaml/wiki/Documentation#Compact_Object_Notation) (yaml parser for java) style:
```yml
arhframe:
  file: !! Arhframe.Util.File(test.php) #will instanciate this: Arhframe\Util\File('test.php') in file var after parsing
```

Import
---------
Import are also hierarchical the last one imported will override the others.

Use yar-import by default in your file:

file1.xml
```yml
<?xml version="1.0" encoding="UTF-8" ?>
<yamlarh>
    <arhframe>
        <var1>var</var1>
    </arhframe>
    <test>arhframe</test>
    <yar-import>file2.yml</yar-import> <!-- you can use a relative path to your yaml file or an absolute -->
</yamlarh>
```

file2.yml
```yml
arhframe:
  var1: varoverride
test2: var3
```

After parsing file1.xml, output will look like (just to have a better format it's show yml):
```yml
arhframe:
  var1: varoverride
test: arhframe
test2: var3
```

Include
---------
You can include a yaml file into another:

file1.yml
```yml
arhframe:
  var1: var
test:
  yar-include:
    - file2.yml #you can use a relative path to your yaml file or an absolute
```

file2.yml
```yml
test2: var3
```

After parsing file1.yml, output will look like:
```yml
arhframe:
  var1: var
test:
  test2: var3
```

**Note**: You can look at these [tests](https://github.com/arhframe/yamlarh/blob/master/tests/Arhframe/Yamlarh/YamlarhTest.php) to know what you can also do.

Extensible
==========

Add a node
----------
After parsing, injecting and importing yamlarh can run your extension.

You have to create a new class which extends `Arhframe\Yamlarh\YamlarhNode` and add it to your yamlarh instance like this:
```php
//create your yamalarh instance before
$yamlarh->addNode("myNodeName", new MyYamlarhNode());
```
Now you can use (for this example) `yar-myNodeName` in your formated file.

**Note**: the `yar-include` is a node take look at [IncludeYamlarhNode](https://github.com/arhframe/yamlarh/blob/master/src/Arhframe/Yamlarh/YamlarhNode/IncludeYamlarhNode.php) to have a good example.