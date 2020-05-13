<?php 

namespace App\Validators;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use Log;

class RestValidator extends Validator {

    /**
     * Add an error message to the validator's collection of messages.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return void
     */
    protected function addError($attribute, $rule, $parameters)
    {
        $message = $this->getMessage($attribute, $rule);

        $message = $this->doReplacements($message, $attribute, $rule, $parameters);

        $customMessage = new MessageBag();

        $customMessage->merge(['code' => strtolower($rule.'_rule_error')]);
        $customMessage->merge(['message' => $message]);

        $this->messages->add($attribute, $customMessage);//dd($this->messages);
    }

    public function validateSite($attribute, $value, $parameters){
        $parsed_url = parse_url( $value );
        return (isset( $parsed_url['host'] ) && preg_match('/^(((?!-))(xn--|_{1,1})?[a-z0-9-]{0,61}[a-z0-9]{1,1}\.)*(xn--)?([a-z0-9][a-z0-9\-]{0,60}|[a-z0-9-]{1,30}\.[a-z]{2,})$/', $parsed_url['host'] ));
    }

}