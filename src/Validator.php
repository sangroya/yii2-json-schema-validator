<?php
namespace sangroya\JsonSchema;

use Opis\JsonSchema\Exception\{
    FilterNotFoundException, InvalidJsonPointerException, InvalidSchemaException,
    SchemaNotFoundException, SchemaKeywordException, UnknownMediaTypeException
};
use Opis\JsonSchema\{
    Schema,
    ISchemaLoader, IFilterContainer, IMediaTypeContainer,  MediaTypeContainer, ISchema, ValidationResult, ValidationError
};
use stdClass;
 
class Validator extends \Opis\JsonSchema\Validator
{
    const BELL = "\x07";

    /** @var IValidatorHelper */
    protected $helper = null;

    /** @var ISchemaLoader|null */
    protected $loader = null;

    /** @var IFilterContainer|null */
    protected $filters = null;

    /** @var IFormatContainer|null */
    protected $formats = null;

    /** @var IMediaTypeContainer|null */
    protected $mediaTypes = null;

    /** @var bool */
    protected $defaultSupport = true;

    /** @var bool */
    protected $varsSupport = true;

    /** @var bool */
    protected $filtersSupport = true;

    /** @var bool */
    protected $mapSupport = true;

    /** @var array */
    protected $globalVars = [];

    /**
     * Validator constructor.
     * @param IValidatorHelper|null $helper
     * @param ISchemaLoader|null $loader
     * @param IFormatContainer|null $formats
     * @param IFilterContainer|null $filters
     * @param IMediaTypeContainer|null $media
     */
    public function __construct(IValidatorHelper $helper = null,
                                ISchemaLoader $loader = null,
                                IFormatContainer $formats = null,
                                IFilterContainer $filters = null,
                                IMediaTypeContainer $media = null)
    {
       
       // parent::__construct($helper,$loader,$formats,$filters,$mdedia);
        $this->helper = $helper ?? new ValidatorHelper();
        $this->formats = $formats ?? new FormatContainer();
        $this->mediaTypes = $media ?? new MediaTypeContainer();
        $this->loader = $loader;
        $this->filters = $filters;
        
    }

    
 /**
     * Validates keywords based on data type
     * @param $document_data
     * @param $data
     * @param array $data_pointer
     * @param array $parent_data_pointer
     * @param ISchema $document
     * @param $schema
     * @param ValidationResult $bag
     * @param array|null $defaults
     * @return bool
     */
    protected function validateProperties(&$document_data, &$data, array $data_pointer, array $parent_data_pointer, ISchema $document, $schema, ValidationResult $bag, array $defaults = null): bool
    {
      
        $type = $this->helper->type($data, true,@$schema->type);
       
        if ($type === 'null' || $type === 'boolean') {
            return true;
        }
        $valid = false;
      
        switch ($type) {
            case 'string':
                $valid = $this->validateString($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
                break;
            case 'date': 
                
                    $valid = $this->validateDate($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
                    break;    
            case 'snumber': 
                $valid = $this->validateStringNumber($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
                break;
            case 'number':
            case 'integer':
                $valid = $this->validateNumber($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
              
                break;
            case 'array':
                $valid = $this->validateArray($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
                break;
            case 'object':
                $valid = $this->validateObject($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag, $defaults);
                // Setup unused defaults
                if (!$valid && $defaults) {
                    $this->setObjectDefaults($data, $defaults);
                }
                break;
        }

        if (!$valid && $bag->isFull()) {
            return false;
        }

        if (property_exists($schema, 'format') && $this->formats) {
            if (!is_string($schema->format)) {
                throw new SchemaKeywordException(
                    $schema,
                    'format',
                    $schema->format,
                    "'format' keyword must be a string, " . gettype($schema->format) . ", given"
                );
            }
           
            $formatObj = $this->formats->get($type, $schema->format);
           
            if ($formatObj === null && $type === 'integer') {
                $formatObj = $this->formats->get('number', $schema->format);
            }
            if ($formatObj !== null) {
                if (!$formatObj->validate($data)) {
                    $valid = false;
                    $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'format', [
                        'type' => $type,
                        'format' => $schema->format,
                    ]));
                    if ($bag->isFull()) {
                        return false;
                    }
                }
            }
            unset($formatObj);
        }

        return $valid;
    }


     /**
     * Validates number/integer keywords
     * @param $document_data
     * @param $data
     * @param array $data_pointer
     * @param array $parent_data_pointer
     * @param ISchema $document
     * @param $schema
     * @param ValidationResult $bag
     * @return bool
     */
    protected function validateStringNumber(/** @noinspection PhpUnusedParameterInspection */
        &$document_data, &$data, array $data_pointer, array $parent_data_pointer, ISchema $document, $schema, ValidationResult $bag): bool
    {
        $ok = true;

        if (property_exists($schema, 'acceptZero')) {
            if (!is_bool($schema->acceptZero) ) {
                throw new SchemaKeywordException(
                    $schema,
                    'acceptZero',
                    $schema->exclusiveMinimum,
                    "'acceptZero' keyword must be an boolean, " . gettype($schema->acceptZero) . " given"
                );
            }
            if ($data[0] =="0") {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'acceptZero', [
                    'expected' => "should not start with zero",
                    'used' => "starting with zero",
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }
        // minimum, exclusiveMinimum
        if (property_exists($schema, 'minimum')) {
            if (!is_int($schema->minimum) && !is_float($schema->minimum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'minimum',
                    $schema->minimum,
                    "'minimum' keyword must be an integer or a float, " . gettype($schema->minimum) . " given"
                );
            }

            $exclusive = false;
            if (property_exists($schema, 'exclusiveMinimum')) {
                if (!is_bool($schema->exclusiveMinimum)) {
                    throw new SchemaKeywordException(
                        $schema,
                        'exclusiveMinimum',
                        $schema->exclusiveMinimum,
                        "'exclusiveMinimum' keyword must be a boolean if 'minimum' keyword is present, " . gettype($schema->exclusiveMinimum) . " given"
                    );
                }
                $exclusive = $schema->exclusiveMinimum;
            }

            if ($exclusive && $data == $schema->minimum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMinimum', [
                    'min' => $schema->minimum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            } elseif ($data < $schema->minimum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'minimum', [
                    'min' => $schema->minimum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        } elseif (property_exists($schema, 'exclusiveMinimum')) {
            if (!is_int($schema->exclusiveMinimum) && !is_float($schema->exclusiveMinimum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'exclusiveMinimum',
                    $schema->exclusiveMinimum,
                    "'exclusiveMinimum' keyword must be an integer or a float if 'minimum' keyword is not present, " . gettype($schema->exclusiveMinimum) . " given"
                );
            }
            if ($data <= $schema->exclusiveMinimum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMinimum', [
                    'min' => $schema->exclusiveMinimum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }

        // maximum, exclusiveMaximum
        if (property_exists($schema, 'maximum')) {
            if (!is_int($schema->maximum) && !is_float($schema->maximum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'maximum',
                    $schema->maximum,
                    "'maximum' keyword must be an integer or a float, " . gettype($schema->maximum) . " given"
                );
            }

            $exclusive = false;
            if (property_exists($schema, 'exclusiveMaximum')) {
                if (!is_bool($schema->exclusiveMaximum)) {
                    throw new SchemaKeywordException(
                        $schema,
                        'exclusiveMaximum',
                        $schema->exclusiveMaximum,
                        "'exclusiveMaximum' keyword must be a boolean is 'maximum' keyword is present, " . gettype($schema->exclusiveMaximum) . " given"
                    );
                }
                $exclusive = $schema->exclusiveMaximum;
            }

            if ($exclusive && $data == $schema->maximum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMaximum', [
                    'max' => $schema->maximum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            } elseif ($data > $schema->maximum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'maximum', [
                    'max' => $schema->maximum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        } elseif (property_exists($schema, 'exclusiveMaximum')) {
            if (!is_int($schema->exclusiveMaximum) && !is_float($schema->exclusiveMaximum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'exclusiveMaximum',
                    $schema->exclusiveMaximum,
                    "'exclusiveMaximum' keyword must be an integer or a float if 'maximum' keyword is not present, " . gettype($schema->exclusiveMaximum) . " given"
                );
            }
            if ($data >= $schema->exclusiveMaximum) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMaximum', [
                    'max' => $schema->exclusiveMaximum
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }

        // multipleOf
        if (property_exists($schema, 'multipleOf')) {
            if (!is_int($schema->multipleOf) && !is_float($schema->multipleOf)) {
                throw new SchemaKeywordException(
                    $schema,
                    'multipleOf',
                    $schema->multipleOf,
                    "'multipleOf' keyword must be an integer or a float, " . gettype($schema->multipleOf) . " given"
                );
            }
            if ($schema->multipleOf <= 0) {
                throw new SchemaKeywordException(
                    $schema,
                    'multipleOf',
                    $schema->multipleOf,
                    "'multipleOf' keyword must be greater than 0"
                );
            }
            if (!$this->helper->isMultipleOf($data, $schema->multipleOf)) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'multipleOf', [
                    'divisor' => $schema->multipleOf
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }

        return $ok;
    }



       /**
     * Validates number/integer keywords
     * @param $document_data
     * @param $data
     * @param array $data_pointer
     * @param array $parent_data_pointer
     * @param ISchema $document
     * @param $schema
     * @param ValidationResult $bag
     * @return bool
     */
    protected function validateDate(/** @noinspection PhpUnusedParameterInspection */
        &$document_data, &$data, array $data_pointer, array $parent_data_pointer, ISchema $document, $schema, ValidationResult $bag): bool
    {
        $ok = true;

        // minimum, exclusiveMinimum
        if (property_exists($schema, 'minimum')) {
            if (!is_int($schema->minimum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'minimum',
                    $schema->minimum,
                    "'minimum' keyword must be an integer, " . gettype($schema->minimum) . " given"
                );
            }
           $expectedDate=strtotime(date("Y-m-d")) - $schema->minimum*86400;
           if(strtotime($data) <= $expectedDate) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'minimum', [
                    'min' => date("Y-m-d",$expectedDate)
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        } elseif (property_exists($schema, 'exclusiveMinimum')) {
            if (!is_int($schema->exclusiveMinimum) ) {
                throw new SchemaKeywordException(
                    $schema,
                    'exclusiveMinimum',
                    $schema->exclusiveMinimum,
                    "'exclusiveMinimum' keyword must be an integer, " . gettype($schema->exclusiveMinimum) . " given"
                );
            }
            $expectedDate=strtotime(date("Y-m-d")) - $schema->exclusiveMinimum*86400;
           
            if (strtotime($data) > $expectedDate) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMinimum', [
                    'min' =>  date("Y-m-d",$expectedDate)
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }

        // maximum, exclusiveMaximum
        if (property_exists($schema, 'maximum')) {
            if (!is_int($schema->maximum) && !is_float($schema->maximum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'maximum',
                    $schema->maximum,
                    "'maximum' keyword must be an integer or a float, " . gettype($schema->maximum) . " given"
                );
            }
            $expectedDate=strtotime(date("Y-m-d")) + $schema->minimum*86400;
            if(strtotime($data) > $expectedDate) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'maximum', [
                    'max' =>  date("Y-m-d",$expectedDate)
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        } elseif (property_exists($schema, 'exclusiveMaximum')) {
            if (!is_int($schema->exclusiveMaximum) && !is_float($schema->exclusiveMaximum)) {
                throw new SchemaKeywordException(
                    $schema,
                    'exclusiveMaximum',
                    $schema->exclusiveMaximum,
                    "'exclusiveMaximum' keyword must be an integer or a float if 'maximum' keyword is not present, " . gettype($schema->exclusiveMaximum) . " given"
                );
            }
            $expectedDate=strtotime(date("Y-m-d")) + $schema->minimum*86400;
            if (strtotime($data) <= $expectedDate) {
                $ok = false;
                $bag->addError(new ValidationError($data, $data_pointer, $parent_data_pointer, $schema, 'exclusiveMaximum', [
                    'max' =>  date("Y-m-d",$expectedDate)
                ]));
                if ($bag->isFull()) {
                    return false;
                }
            }
        }

    

        return $ok;
    }
}
