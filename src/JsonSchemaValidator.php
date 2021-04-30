<?php
namespace sangroya\JsonSchema;

use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;


/**
 * JsonSchemaValidator validates a value against a JSON Schema file.
 *
 * The URI of the schema file must be defined via the [[schema]] property.
 *
 * @author Parveen
 */
class JsonSchemaValidator extends Validator
{
    /**
     * @var string The URI of the JSON schema file.
     */
    public $schema;

    /**
     * @var string User-defined error message used when the schema is missing.
     */
    public $schemaEmpty;

    /**
     * @var string User-defined error message used when the schema isn't a string.
     */
    public $schemaNotString;

    /**
     * @var string User-defined error message used when the value is not a string.
     */
    public $notString;

    /**
     * @var string User-defined error message used when the value is not a valid JSON string.
     */
    public $notJsonString;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->schemaEmpty === null) {
            $this->schemaEmpty = 'The "schema" property must be set.';
        }

        if ($this->schemaNotString === null) {
            $this->schemaNotString = 'The "schema" property must be a a string.';
        }

        if ($this->message === null) {
            $this->message = Yii::t('app', '{property}: {message}.');
        }

        if ($this->notString === null) {
            $this->notString = Yii::t('app', 'The value must be a string.');
        }

        if ($this->notJsonString === null) {
            $this->notJsonString = Yii::t('app', 'The value must be a valid JSON string.');
        }

        if (empty($this->schema)) {
            throw new InvalidConfigException($this->schemaEmpty);
        }

        if (!is_string($this->schema)) {
            throw new InvalidConfigException($this->schemaNotString);
        }
    }

    public function validateAttribute($model,$attribute,$file){
        // use Opis\JsonSchema\{
        //     Validator, ValidationResult, ValidationError, Schema
        // };
        
        
        if (!is_string($model->$attribute)) {
            $this->addError($model, $attribute, $this->notString);
            return;
        }

        $data = json_decode($model->$attribute);
        if (json_last_error()) {
            $this->addError($model, $attribute, $this->notJsonString);
            return;
        }

// echo file_get_contents($file);die;
        $schema = \Opis\JsonSchema\Schema::fromJsonString(file_get_contents($file));
       
        $validator = new \Opis\JsonSchema\Validator();
       
        /** @var ValidationResult $result */
        $result = $validator->schemaValidation($data, $schema,20);
        $errorFormat=new ErrorFormat();
                if ($result->isValid()) {
            echo '$data is valid', PHP_EOL;
        } else {
            /** @var ValidationError $error */
            $errors = $result->getErrors();
            // echo $result->totalErrors();
            // print_r($result->getErrors());die;
            foreach($errors as $key=>$error){
// print_r($error);die;
                $path=(implode(".", $error->dataPointer()));
               // print_r( $error->dataPointer());
               
                //print_r($error);
               // die;
           // echo '$data is invalid', PHP_EOL;
        //    echo $key. PHP_EOL;
           
        //    echo "Error: ", $error->keyword(), PHP_EOL;
        //    echo $path;
           $type=$error->keyword();
            if($missing= $error->keywordArgs()["missing"])
            $path= $path. ".". $missing;
            $errorsArray=$error->keywordArgs();
            if(!isset($errorsArray['missing']))
            $errorsArray['missing']='';
            $errorsArray['path']=$path;
            // print_r($error);die;
            if(in_array($type,['oneOf'])){
                $errorsArray['rule']=(json_encode($error->schema()->oneOf));
               
            }
            if($subErrors=$error->subErrors()){
                
                if(in_array($type,['allOf','else','then']))
                { 
                       $rs=$this->grabSubErrors($subErrors);
                        foreach($rs as $rsError){
                        
                            $stype=$rsError["type"];
                            $errorsSubArray=$rsError["errorsArray"];
                            
                            $this->addError(
                                $model,
                                $errorsSubArray['path'],
                                $this->message,
                                [
                                    'type'=>$stype,
                                    'property' => $errorsSubArray['path'],
                                    'message' => ucfirst($errorFormat->format($stype,$errorsSubArray)),
                                ]
                            );
                            $model->rawJsMessage[]=[
                                'type'=>$stype,
                                'property' => $errorsSubArray['path'],
                                'message' => ucfirst($errorFormat->format($stype,$errorsSubArray)),
                            ];
                           
                        }
                       
                       continue;
                }
               else
                $errorsArray['path']= implode(".",$subErrors[0]->dataPointer())  ;
            }
           
            $this->addError(
                $model,
                $errorsArray['path'],
                $this->message,
                [
                    'type'=>$type,
                    'property' => $errorsArray['path'],
                    'message' => ucfirst($errorFormat->format($type,$errorsArray)),
                ]
            );
          
            $model->rawJsMessage[]=[
                'type'=>$type,
                'field' => $errorsArray['path'],
                'message' => ucfirst($errorFormat->format($type,$errorsArray)),
            ];
            }
        }

    }

    protected function grabSubErrors($errors,$parentType=""){
        
        $errorsArrays=[];
       
        foreach($errors as $error){
            $errorsArray=[];
           
            if(in_array($error->keyword(),['oneOf'])){
                $errorsArray['rule']=(json_encode($error->schema()->oneOf));
               
            }
            else if($subErrors=$error->subErrors())
            {                
               return $this->grabSubErrors($subErrors,$error->keyword());
            }

            
                $type=$error->keyword();
                if($type=="not"){
                   
                    $type.="Required";
                    $errorsArray['missing']=$error->schema()->not->required[0];
                   $errorsArray['path']= implode(".",$error->dataPointer()) .".".  $errorsArray['missing'] ;
                   
                   
                   }else {
                    $errorsArray['missing']=$error->keywordArgs()["missing"];
                    if($errorsArray['missing'])
                    $errorsArray['path']= implode(".",$error->dataPointer()) .".".  $errorsArray['missing'] ;
                    else 
                    $errorsArray['path']= implode(".",$error->dataPointer()) ;
                   }
               $errorsArrays[]= ["type"=>$type,"errorsArray"=> $errorsArray];
                
                
            
        }
       return $errorsArrays;
        
    }

    /**
     * @inheritdoc
     */
    public function validateAttributeOld($model, $attribute)
    {

        
        

        if (!is_string($model->$attribute)) {
            $this->addError($model, $attribute, $this->notString);
            return;
        }

        $value = json_decode($model->$attribute);
        if (json_last_error()) {
            $this->addError($model, $attribute, $this->notJsonString);
        }

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve($this->schema);

        $validator = new JSValidator();
        $validator->check($value, $schema);

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
               
                $this->addError(
                    $model,
                    $error['property'],
                    $this->message,
                    [
                        'property' => $error['property'],
                        'message' => ucfirst($error['message']),
                    ]
                );
              
                $model->rawJsMessage[]=[
                    'field' => $error['property'],
                    'message' => ucfirst($error['message']),
                ];
            }
        }
    }

    /**
     * Validates a value.
     *
     * @param string $value A JSON encoded string to validate.
     * @return array|null An array of error data, or null if the data is valid.
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            return [$this->notString, []];
        }

        $value = json_decode($value);
        if (json_last_error()) {
            return [$this->notJsonString, []];
        }

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve($this->schema);

        $validator = new JSValidator();
        $validator->check($value, $schema);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $error = reset($errors);
            return [$this->message, ['property' => $error['property'], 'message' => ucfirst($error['message'])]];
        }

        return null;
    }
}
