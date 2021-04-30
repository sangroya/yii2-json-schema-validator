<?php
namespace sangroya\JsonSchema;

use Opis\JsonSchema\Exception\{
    FilterNotFoundException, InvalidJsonPointerException, InvalidSchemaException,
    SchemaNotFoundException, SchemaKeywordException, UnknownMediaTypeException
};
use Opis\JsonSchema\{
    ISchemaLoader, IFormatContainer, IFilterContainer, IMediaTypeContainer,  MediaTypeContainer
};
use stdClass;

class Validator extends \Opis\JsonSchema\Validator
{
   
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
        $type = $this->helper->type($data, true);
        if ($type === 'null' || $type === 'boolean') {
            return true;
        }

        $valid = false;

        switch ($type) {
            case 'string':
                $valid = $this->validateString($document_data, $data, $data_pointer, $parent_data_pointer, $document, $schema, $bag);
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
           
            if ($formatObj === null && $type === 'string' && $schema->format=="number") {
                $formatObj = $this->formats->get('number', $schema->format);
                
            }
           
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

   

}