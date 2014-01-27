yamlarh
=======

Yml injector for arhframe in standalone.
You can inject into your yaml:
  * object
  * constant from scope 
  * Variable from global scope
  * Variable from yaml file

You can also import other yaml inside a yaml file for overriding

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

$yamlarh = new Yamlarh(__DIR__.'/path/to/yaml/file');
$array = $yamlarh->parse();
```

Exemple
=========

Variable injection
---------

Variable injection is hierarchical, it will find in this order:
  1. In the yaml file with import
  2. In your global scope
  3. In you constant

Yaml file:
```yml
arhframe:
  myvar1: test
  myvar2: %arhframe.myvar1%
  myvar3: %var3%
  myvar4: %VARCONSTANT%
```

Php file:
```php
use Arhframe\Yamlarh\Yamlarh;
$var3 = 'testvar';
define('VARCONSTANT', 'testconstant');
$yamlarh = new Yamlarh(__DIR__.'/test.yml');
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
          )

  ) 
```
