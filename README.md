# Yii 2 JSON Schema Validator

A Yii 2 extension that provides a validation class that wraps
[JSON Schema for PHP](https://github.com/justinrainbow/json-schema).

## Installation

```
$ composer require sangroya/yii2-json-schema-validator
```

## Usage

Model class example:

```php
<?php

namespace app\models;

use sangroya\jsonschema\JsonSchemaValidator;
use Yii;
use yii\base\Model;

class MyModel extends Model
{
    public $json;

    public function rules()
    {
        return [
            [
                'json',
                JsonSchemaValidator::className(),
                'schema' => 'file://' . Yii::getAlias('@app/path/to/schema.json'),
                /* or URL
                'schema' => 'https://test-example.com/path/to/schema.json',
                */
            ],
        ];
    }
}
```

See [json-schema](http://json-schema.org) for details how to describe JSON schema.
