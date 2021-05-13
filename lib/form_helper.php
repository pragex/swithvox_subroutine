<?php
# require_once(BASE_DIR. "lib/email.php");
require_once("email.php");

if(!function_exists("array_column"))
{
    function array_column($array,$column_name)
    {
        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
    }
}

class FormHelper
{
    private $choices;
    private $fields;
    public $cleanedData;
    public $errorInFields;

    function __construct($fields=[], $choices = []) 
    {
        $this->choices = $choices;   
        $this->fields = $fields;
        $this->cleanedData = false;
        $this->errorInFields = false;
    }

    
    public function renameCleanedData($str, $offset = 0)
    {
        $keys = [];
        
        if(empty($this->cleanedData))
            return;

        foreach(array_keys($this->cleanedData) as $key)
        {
            if(strncmp($str, substr($key, $offset), strlen($str)) === 0)
            {
                $keys[] = substr($key, strlen($str) + $offset);
            }else
            {
                $keys[] = $key;
            }
        }
    
        $this->cleanedData = array_combine($keys, array_values($this->cleanedData));
    }

    public function choice($name)
    {
        $name = strtolower(trim($name));

        if(key_exists($name, $this->choices))
            return $this->choices[$name];
        
        return null;
    }

    private function testBetween($value, $min, $max)
    {


        return 0;
    }

    function field($name)
    {
        $name = strtolower(trim($name));

        if(key_exists($name, $this->fields))
            return $this->fields[$name];

        return null;
    }

    public function fields($excludes = [])
    {
        return array_diff(array_keys($this->fields), $excludes);
    }



    public function fieldType($name)
    {
        $name = strtolower(trim($name));
        return $this->fields[$name]['type'];
    }

    public function fieldWidget($name)
    {
        $name = strtolower(trim($name));

        if(key_exists('widget', $this->fields[$name]))
            return $this->fields[$name]['widget'];

        if($this->fields[$name]['type'] == "choices")
            return "select";

        if($this->fields[$name]['type'] == "boolean")
            return "checkbox";

       return $this->fields[$name]['type'];
    }


    public function renderLabel($name, $classCss = "", $label = null)
    {
        $field = $this->field($name);
        if(! $field)
            return "";

        $htmName = htmlspecialchars($name);

        if(! empty($field['class_css']))
            $classCss = $field['class_css'] . "; " . $classCss;

        $attributes = self::attrStringToArray($classCss, "class");

        if($label !== null)
        {
            $htmLabel = htmlspecialchars($label);
        }elseif(!empty($field['label']))
        {
            $htmLabel = htmlspecialchars($field['label']);
        }else
        {
            $htmLabel = "";
        }

        $htmLabel = str_replace('\ ', "&nbsp;", $htmLabel);

        echo "<label ". self::attrArrayToString($attributes, "label") ."for=\"$htmName\">$htmLabel</label>\n";
    }

    public function renderField($name, $classCss = "", $value = null, $attributes = [])
    {
        $field = $this->field($name);
        $html = "";

        if(! $field)
            return "";

        $required = key_exists('empty', $field) ? ! $field['empty'] : false;
        $widget = key_exists('widget', $field) ? $field['widget'] : "";

        if(($value === null || $value === []) && key_exists('initial', $field) && $field['initial'] !== null)
            $value = $field['initial'];

        if(! empty($field['class_css']))
        {
            $classCss = $field['class_css'] . "; " . $classCss;
        }

        $attributesCss = self::attrStringToArray($classCss, "class");

        if(key_exists('attributes', $field) && is_array($field['attributes']))
            $attributes = array_merge($attributes, $field['attributes'], $attributesCss);
        elseif($attributesCss)
            $attributes = array_merge($attributes, $attributesCss);

        switch($field['type'] ?: "")
        {
        case "email":
        case "text":
        case "number":
        case "integer":
        case "float":
            if(! $widget)
                $widget = "input";

            if($widget == 'textarea')
            {
                $fieldType = 'textarea';

            }else if($field['type'] == "integer" || $field['type'] == "float")
            {
                $fieldType = "text";
            }else
            {
                $fieldType = $field['type'];
            }

            foreach(['placeholder', 'min', 'max', 'minlength', 'maxlength', 'step'] as $attrName)
            {
                if(key_exists($attrName, $field))
                    $attributes[] = [ $widget, $attrName, $field[$attrName] ];
            }

            $html = self::textField($name, $required, $fieldType, $value, $attributes);
            break;

        case "boolean":
            $yesNo = key_exists('yes_no', $field) ? $field['yes_no'] : "oui;non";

            $html = self::booleanField($name, $required, $widget, $value, $yesNo, $attributes);
            break;

        case "choices":
            $choices = key_exists('choices', $field) ? $field['choices'] : $this->choice($name);
            $html = self::choiceField($name, $required, $widget, $choices, $value, $attributes);
            break;

        default :
            $html = "";
            break;
        }

        echo $html;
    }


    public function validForm($data)
    {       
        $this->cleanedData = [];
        $this->errorInFields = [];
        
        foreach($this->fields() as $name)
        {
            $field = $this->field($name);
            if(key_exists($name, $data))
            {
                if(! key_exists('trim', $field) || $field['trim'] == true)
                {
                    if(is_array($data[$name]))
                    {
                        $values = array_filter(array_map('trim', $data[$name]));
                    }else
                    {
                        $values = [trim($data[$name])];
                    }
                }else
                {
                    if(is_array($data[$name]))
                    {
                        $values = array_filter($data[$name]);
                    }else
                    {
                        $values = [$data[$name]];
                    }
                }
            }else
            {
                $values = [];
            }

            # Vérifie si le champ est requis.
            if(key_exists('empty', $field) && $field['empty'] == false && ! array_filter($values, "strlen"))
            {
                // Vérifie si valeur dans le tableau
                $this->errorInFields[$name] = "Ce champ est requis.";
                continue;
            }

            if($field['type'] === 'choices')
            {
                $this->cleanedData[$name] = null;
            }

            foreach($values as $value)
            {
                $cleaned = null;

                switch($field['type'])
                {
                case 'integer':
                    if($value !== "" && $value !== null && ! preg_match('/^[+\-]?\d+$/', $value))
                    {
                        $this->errorInFields[$name] = "Une valeur entière est requis.";
                    }else
                    {
                        $cleaned = intval($value);
                    }
                    break;
        
                case 'number':
                case 'float':
                    if($value !== "" && $value !== null && ! preg_match('/^[+\-]?[.,]?\d+[.,]?\d*$/', $value))
                    {
                        $this->errorInFields[$name] = "Une valeur décimal est requis.";
                    }else
                    {
                        $cleaned = floatval(str_replace(",", ".", $value));
                    }
                    break;

                case 'email':
                    if($value !== "" && $value !== null && ! preg_match(EMAIL_REGEX, $value))
                    {
                        $this->errorInFields[$name] = "Une adresse courriel est requis.";
                    }else
                    {
                        $cleaned = $value;
                    }
                    break;
        
                case 'choices':

                    if($value !== "" && $value !== null && ! in_array($value, array_column($this->choices[$name], 0)))
                    {
                        $this->errorInFields[$name] = "Vous devez sélectionner l'un des choix proposés.";
                    }else
                    {
                        $cleaned = $value;
                    }
                    break;
        
                case 'boolean':
                    # $cleaned = boolval($value);

                    if(is_string($value))
                    {
                        $value = strtolower($value);
                        $cleaned = ($value !== "false" && $value !== "no" && $value !== "" && $value !== "0") ? 1 : 0;

                    }elseif ($value !== null)
                    {
                        $cleaned = $value ? 1 : 0;
                    }

                    break;       

                default:
                    $cleaned = $value;
                    break;
                }


                if($cleaned !== null)
                {

                    if(key_exists('min', $field) && key_exists('max', $field) && ($cleaned < $field['min'] || $cleaned > $field['max']))
                    {
                        $this->errorInFields[$name] = "La valeur doit être compris entre {$field['min']} et {$field['max']}.";

                    }elseif(key_exists('min', $field) && $cleaned < $field['min'])
                    {
                        $this->errorInFields[$name] = "La valeur doit être supérieur à {$field['min']}.";

                    }elseif(key_exists('max', $field) && $cleaned > $field['max'])
                    {
                        $this->errorInFields[$name] = "La valeur doit être inférieur à {$field['max']}.";
                    }

                    if($cleaned && key_exists('minlength', $field) && key_exists('maxlength', $field) && 
                       (strlen($cleaned) < $field['minlength'] || strlen($cleaned) > $field['maxlength']))
                    {
                        $this->errorInFields[$name] = "La longueur de ce champ doit être compris entre {$field['minlength']} et {$field['maxlength']} caractères.";

                    }elseif($cleaned && key_exists('minlength', $field) && strlen($cleaned) < $field['minlength'])
                    {
                        $this->errorInFields[$name] = "La longueur de ce champ doit être supérieur à {$field['minlength']} caractères.";

                    }elseif($cleaned && key_exists('maxlength', $field) && strlen($cleaned) > $field['maxlength'])
                    {
                        $this->errorInFields[$name] = "La longueur de ce champ doit être inférieur à {$field['maxlength']} caractères.";
                    }


                    if(key_exists($name, $this->cleanedData) && $this->cleanedData[$name] != "")
                    {
                        if(is_array($this->cleanedData[$name]))
                        {
                            $this->cleanedData[$name][] = $cleaned;
                        }else
                        {
                            $this->cleanedData[$name] = [$this->cleanedData[$name], $cleaned];
                        }
                    }else
                    {
                        $this->cleanedData[$name] = $cleaned;
                    }
                }
            }
        }

        return (count($this->errorInFields) == 0);
    }


    private static function choiceField($name, $required = false, $type = null, $choices = null, $values = null, $attributes = null)
    {
        $htmName = htmlspecialchars($name);
        $required = $required ? "required=\"required\"" : "";
        
        if($choices === null)
            return "";        

        if($values !== null && ! is_array($values))
        {
            $values = [$values];
        }

        switch($type)
        {
        case "radio":
        case "checkbox":
            $html ="";

            for($i=0; $i < count($choices); $i++)
            {
                $html .= "<div " . self::attrArrayToString($attributes, 'div') . ">\n";
                
                $htmLabel = $choices[$i][2] ? htmlspecialchars($choices[$i][2]) : "";
                $htmValue = ($choices[$i][0] !== null) ? htmlspecialchars($choices[$i][0]) : "";

                if($values !== null)
                {
                    $checked = in_array($choices[$i][0], $values, true) ? "checked=\"checked\"" : "";
                }else
                {
                    $checked = ($choices[$i][1] ? "checked=\"checked\"" : "");
                }

                $html .= "<input " . self::attrArrayToString($attributes, "choices.input.$type") . 
                         "type=\"$type\" id=\"{$htmName}_$i\" name=\"{$htmName}\" $checked value=\"$htmValue\">\n";
                $html .= "<label " . self::attrArrayToString($attributes, "choices.label.$type") . "for=\"{$htmName}_$i\">$htmLabel</label>\n";
            
                $html .= "</div>\n";
            }

            break;
        default:
            $html = "<select $required " . self::attrArrayToString($attributes, 'choices.select') . "name=\"$htmName\" id=\"$htmName\">\n";

            foreach($choices as $item)
            {
                $htmLabel = $item[2] ? htmlspecialchars($item[2]) : "";
                $htmValue = $item[0] !== null ? htmlspecialchars($item[0]) : "";

                if($values !== null)
                {
                    $selected = in_array($item[0], $values, true) ? "selected=\"selected\"" : "";
                }else
                {
                    $selected = ($item[1] ? "selected=\"selected\"" : "");
                }

                $html .= "<option " . self::attrArrayToString($attributes, "choices.option") . "value=\"$htmValue\" $selected>$htmLabel</option>\n";
            }

            $html .= "</select>\n";
            break;
        }

        return $html;
    }


    private static function booleanField($name, $required = false, $type = null, $value = null, $yesNo="oui;non", $attributes = null)
    {
        $htmName = htmlspecialchars($name);
        $htmYesNo = explode(";", htmlspecialchars($yesNo . ";"));
        $required = $required ? "required=\"required\"" : "";
        
        

        switch($type)
        {
        case "radio":
            $html = "";

            for($i=0; $i < 2; $i++)
            {
                if(($i === 0 && $value) || ($i === 1 && ! $value))
                    $checked = "checked=\"checked\"";
                else
                    $checked = "";

                $html .= "<div " . self::attrArrayToString($attributes, 'div') . ">\n";
                $html .= "<input " . self::attrArrayToString($attributes, "boolean.input.radio") . 
                         "type=\"radio\" id=\"{$htmName}_$i\" name=\"$htmName\" $checked>\n";
                $html .= "<label " . self::attrArrayToString($attributes, "boolean.label.radio") . "for=\"{$htmName}_$i\">{$htmYesNo[$i]}</label>\n";
                $html .= "</div>\n";    
            }
            break;

        default:
            $checked = ($value ? "checked=\"checked\"" : "");

            $html = "<div " . self::attrArrayToString($attributes, 'div') . ">\n";
            $html .= "<input $required " . self::attrArrayToString($attributes, "boolean.input.checkbox") . 
                     "type=\"checkbox\" id=\"$htmName\" name=\"$htmName\" $checked>\n";
            $html .= "<label " . self::attrArrayToString($attributes, "boolean.label.checkbox") . " for=\"$htmName\">{$htmYesNo[0]}</label>\n";
            $html .= "</div>\n";
            break;
        }

        return $html;
    }

    
    private static function textField($name, $required = false, $type = null, $value = null, $attributes = null)
    {
        $htmName = htmlspecialchars($name);
        if($value && is_array($value))
        {
            $htmValue = htmlspecialchars($value[0]);
        }elseif($value != "")
        {
            $htmValue = htmlspecialchars($value);
        }else
        {
            $htmValue = "";
        }

        $required = $required ? "required=\"required\"" : "";

        $type = $type ? $type : "text";

        if($type === "textarea")
        {
            $html = "<textarea " . self::attrArrayToString($attributes, "text.textarea.$type") . 
                    "id=\"$htmName\" name=\"$htmName\" $required>$htmValue</textarea>";
        }else
        {
            $html = "<input " . self::attrArrayToString($attributes, "text.input.$type") . 
                    "type=\"$type\" id=\"$htmName\" name=\"$htmName\" value=\"$htmValue\" $required>\n";
        }

        return $html;
    }


    private static function attrStringToArray($str, $attrName)
    {
        $attributes = [];

        foreach(explode(";", $str) as $item)
        {
            $tagValue =  explode(":", $item, 2);

            if(count($tagValue) == 2 && trim($tagValue[1]) != "")
            {
                $attributes[] = [trim($tagValue[0]), $attrName, trim($tagValue[1])];
            }elseif(trim($tagValue[0]) != "")
            {  
                $attributes[] = ["", $attrName, trim($tagValue[0])];
            }   
        }

        return $attributes;
    }

    private static function attrArrayToString($attributes, $elemName)
    {
        $html = " ";
        $attrArray = [];

        foreach($attributes as $item)
        {
            $tag = strtolower($item[0]);

            if($tag === "" || preg_match(self::attrFilter($tag), $elemName))
            {
                $attrName = strtolower($item[1]);
                $value = $item[2];

                if(key_exists($attrName, $attrArray) && $attrArray[$attrName] != "")
                {
                    $attrArray[$attrName] .= " " . $value;
                }else
                {
                    $attrArray[$attrName] = $value;
                }
            }
        }

        $html = "";
        foreach($attrArray as $key => $value)
        {
            $html .= "$key=\"". htmlspecialchars($value) . "\" ";
        }

        return $html;
    }


    private static function attrFilter($str)
    {
        $str = preg_quote($str);
        $str = str_replace(['\\?', '\\*', '\\+', '\\|', '\\^'], ['.', '.*', '.+', '|', '^'], $str);
        
        if(strpos($str, "|") !== false)
            $reg = '/(' . $str. ')/';
        else
            $reg = '/' . $str. '/';
            
        return $reg;
    }

}



