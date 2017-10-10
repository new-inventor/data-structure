# Property bag
This utility provide to you data structure metadata and some useful classes.

With this packet you can transform arrays to needed format and load it to objects recursive.

**Objects to load** MUST implement Loadable or DataStructureInterface interface.   

#### Installation
composer require new-inventor/data-structure

## Examples
### Metadata config
```yaml
namespace: DataStructure\Namespace
validation:
  constraints:
    - Callback: ['Some\Existing\Class', 'GetTrue']
  getters:
    prop1:
      - GreaterThan: 0
  properties:
    prop0:
      - GreaterThan: 0
properties:
  prop1: NewInventor\DataStructure\Transformer\ToInt
  prop2:
    transformers:
      - ToInt: ~
    validation:
      - GreaterThan: 5
      - LessThanOrEqual: 1000
  prop3:
    transformers:
      - ToBool:
          - ['Some\Existing\Class', 'GetTrue']
  prop4:
    transformers:
      - ToBool:
          - groups: forward
      - BoolToMixed:
          - static: ['Some\Existing\Class', 'bbb']
          - const: ['Some\Existing\Class', 'AAA']
          - groups: backward
  prop5: ~
  prop6:
    transformers:
      - ChainTransformer:
          - ToString: ~
          - CsvStringToArray: ~
          - NewInventor\DataStructure\Transformer\InnerTransformer:
              - ToInt: ~
          - groups: forward
      - ArrayToCsvString:
          - groups: backward
  prop7:
    default: 2222
    transformers:
      - ToString: ~
      - StringToDateTime:
          - 'd.m.Y'
          - groups: forward
  prop8:
    transformers:
      - ToArray:
          - groups: forward
  prop9:
    nested:
      class: DataStructure\Namespace\Structure
```
#### Recursive loading
You can load properties recursive by using RecursiveConstructor.

To use this you shoul add `nested` parameter to property like this

```yaml
prop:
  nested:
    class: DataStructure\Namespace\Structure
```
or
```yaml
prop:
  nested:
    metadata:
      path: some/config
      baseNamespace: Some\Namespace
    class: DataStructure\Namespace\Structure
```
or
```yaml
prop:
  nested:
    metadata:
      path: some/config/path.yml
    class: DataStructure\Namespace\Structure
```

#### Namespace
It is the namespace for data structure class for recursive loading.

#### Validation
It is the symfony class validation config. You can pass it directly into property

#### Properties
It is properties of your data structure.

Property has this parameters:
* default
* transformers
* validation
* nested

If property === null(~) then default = null and no transformers and validators
If property is string then default = null and default transformers = this string

##### transformers
Transformers is array of arrays.

In yml like this:

```yaml
key:
  - name: value 
```

So you can set transformer like this:
```yaml
transformers:
  - ToInt: ~
```

By default transformer is inner transformer, but you can create you own and pass full class name to transformer config

Transformer can receive array or parameters and parameter can be array(1) with reserved word as key;
Reserved words:
* static - interpret as static property of class `['class', 'propertyName']`
* const - interpret as constant can be array like static or string
* groups - transformer groups, by default `groups = ['default']`.

If in transformers section more than 1 transformer in same group then this transformers will be wrapped by ChainTransformer, so this identical:
```yaml
transformers:
  - ToInt:
      - groups: group1
  - ToRange:
      - 1
      - 10
      - groups: group1
```
and
```yaml
transformers:
  - ChainTransformer:
      - groups: group1
      - ToInt:
      - ToRange:
          - 1
          - 10
```

##### Validation
It is the getters part of symfony validation config for given property

##### Default
Default can be any value and have same reserved keys as transformer parameters, but group.

##### Nested 
It is described in Recursive loading section.

### PropertiesTransformer
```php
$metadata = (new Metadata\Loader($path))->loadMetadata();
$transformer = $metadata->getTransformer($groupName);
$resArray = $transformer->transform($someArray);
```

or you can compose PropertiesTransformer by yourself

### RecursiveConstructor
This constructor construct class with metadata by recursive loading it nested classes.

## Links
* To use only transformers use https://github.com/new-inventor/transformers
* To implement property bags use https://github.com/new-inventor/property-bag