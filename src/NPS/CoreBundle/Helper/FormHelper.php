<?php
namespace NPS\CoreBundle\Helper;

use Symfony\Component\Form\Form,
    Symfony\Component\Templating\Helper\Helper;

/**
 * Class for time functions
 */
class FormHelper extends Helper
{
    public $name = 'FormHelper';

    /**
     * Return array of form errors
     *
     * @param Form $form Form
     *
     * @return array
     */
    public static function getErrorList(Form $form)
    {
        $errors = array();
        $errors['hasErrors'] = false;

        // Check all form input for errors
        foreach ($form->all() as $key => $err) {
            $errors[$key]="";
            if ($err->hasErrors()) {
                $errors['hasErrors'] = true;
                foreach ($err->getErrors() as $err) {
                    $errors[$key]=$err->getMessageTemplate();
                }
            }
        }

        return $errors;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get from array of objects to array for select
     * @param object $objects array of objects
     * @param string $key     name of function to get ID of variables
     * @param string $value   name of function to get variables for select
     *
     * @return array
     */
    public static function getArrayForSelect($objects, $key = 'getId', $value ='getName')
    {
        $selectArray = array();
        foreach ($objects as $object) {

            if (method_exists($object, $value)) {
                $selectArray[$object->$key()] = $object->$value();
            }
        }

        return (count($selectArray))? $selectArray : null ;
    }
}
