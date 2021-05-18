<?php 
namespace sangroya\JsonSchema;

class ErrorFormat{
    protected $map=[
        'additionalItems'=>'The attribute must not contain additional items',
        'additionalProperties'=>'The property {path} is not defined and the definition does not allow additional properties',
        'required'=>"The property {missing} is required",
        'notRequired'=>"The property {path} should not be present",
        'const'=>"The property {path} value expected to be {expected}",
        'enum'=>'The property {path} value must be one of the following values: {expected}.',
        'type'=>'{used} value found, but a {expected} is required',
        'oneOf'=>'The attribute must match exactly one of the subschemas. One of {rule}',
        'maxLength'=>'The attribute length may not be greater than {max} characters',
        'minLength'=>'The attribute length must be atleast {min} characters',
        'minimum'=>'The attribute value must be greater than or equal {min}',
        'maximum'=>'The attribute value must be smaller than or equal {max}',
        'exclusiveMinimum'=>'The attribute value must be smaller than or equal {min}',
        'exclusiveMaximum'=>'The attribute value must be greater than or equal {max}',
        'pattern'=>'Does not match the regex pattern of {pattern}',
         'format'=>'The attribute should match {format} format',
         'acceptZero'=>'The property {path} {expected} but {used}'

    ];
    public function format($type,$values)
    {
        $msg='msg not found for '.$type . json_encode($values);
        
    //    print_r($values);
        if(@$values['expected'] && is_array($values['expected']))
        $values['expected'] = json_encode($values['expected']);
        $pattern=@$values['pattern'];
        if($pattern){
            switch($pattern){
                case "^[0-9]+$":
                    $pattern="integer";
                break;
            }
        }
        if(isset($this->map[$type]))
        $msg=str_replace(['{missing}','{path}','{expected}','{used}','{rule}','{max}','{min}','{pattern}','{format}'],
        [
            @$values['missing'],
            @$values['path'],
            @$values['expected'],
            @$values['used'],
            @$values['rule'],
            @$values['max'],
            @$values['min'],
           
           $pattern,
           @$values['format'],
        ],$this->map[$type]);
        
        return $msg;
    }
}