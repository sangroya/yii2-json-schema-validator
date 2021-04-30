<?php
namespace sangroya\JsonSchema;
use \Opis\JsonSchema\{
    IFormat,IFormatContainer
};
class FormatContainer implements IFormatContainer
{

    /** @var array */
    protected $formats = [
        'string' => [
            'date' => \Opis\JsonSchema\Formats\Date::class,
            'date-time' => \Opis\JsonSchema\Formats\DateTime::class,
            'email' => \Opis\JsonSchema\Formats\Email::class,
            'idn-email' => \Opis\JsonSchema\Formats\IdnEmail::class,
            'hostname' => \Opis\JsonSchema\Formats\Hostname::class,
            'idn-hostname' => \Opis\JsonSchema\Formats\IdnHostname::class,
            'ipv4' => \Opis\JsonSchema\Formats\IPv4::class,
            'ipv6' => \Opis\JsonSchema\Formats\IPv6::class,
            'json-pointer' => \Opis\JsonSchema\Formats\JsonPointer::class,
            'regex' => \Opis\JsonSchema\Formats\Regex::class,
            'relative-json-pointer' => \Opis\JsonSchema\Formats\RelativeJsonPointer::class,
            'time' => \Opis\JsonSchema\Formats\Time::class,
            'uri' => \Opis\JsonSchema\Formats\Uri::class,
            'uri-reference' => \Opis\JsonSchema\Formats\UriReference::class,
            'uri-template' => \Opis\JsonSchema\Formats\UriTemplate::class,
            'iri' => \Opis\JsonSchema\Formats\Iri::class,
            'iri-reference' => \Opis\JsonSchema\Formats\IriReference::class,
            'uuid' => \Opis\JsonSchema\Formats\Uuid::class,
            "number"=>Formats\Number::class,
        ]
    ];

    /**
     * @inheritDoc
     */
    public function get(string $type, string $name)
    {
        if (!isset($this->formats[$type][$name])) {
            return null;
        }
        if (is_string($this->formats[$type][$name])) {
            $class = $this->formats[$type][$name];
            $this->formats[$type][$name] = new $class();
            if (!($this->formats[$type][$name] instanceof IFormat)) {
                unset($this->formats[$type][$name]);
                return null;
            }
        }
        return $this->formats[$type][$name];
    }

    /**
     * @param string $type
     * @param string $name
     * @param IFormat $format
     * @return FormatContainer
     */
    public function add(string $type, string $name, IFormat $format): self
    {
        $this->formats[$type][$name] = $format;
        return $this;
    }

}
