<?php
namespace sangroya\JsonSchema;

use Opis\JsonSchema\Exception\{
    FilterNotFoundException, InvalidJsonPointerException, InvalidSchemaException,
    SchemaNotFoundException, SchemaKeywordException, UnknownMediaTypeException
};
use Opis\JsonSchema\{
    Schema,ValidatorHelper,
    ISchemaLoader, IFilterContainer, IMediaTypeContainer,  MediaTypeContainer, ISchema, ValidationResult, ValidationError,IValidatorHelper
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
        $this->helper = $helper ?? new ValidatorHelper();
        $this->formats = $formats ?? new FormatContainer();
        $this->mediaTypes = $media ?? new MediaTypeContainer();
        $this->loader = $loader;
        $this->filters = $filters;
      
    }

    


}