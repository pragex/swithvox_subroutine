<?php

# die(dirname(__FILE__) . "/../../lib/switchvox_response.php");

require_once(dirname(__FILE__) . "/../../lib/switchvox_response.php");

require_once("form.php");


$form = new MyForm();
if($form->validForm($_GET) === false)
{
    http_response_code(400);
    include(dirname(__FILE__) . "/../../template/error.php");
    exit();
}

$doc = new SwichvoxResponse();

$text = array_key_exists('text', $form->cleanedData) ? $form->cleanedData['text'] : "";
$start = array_key_exists('start', $form->cleanedData) ? $form->cleanedData['start'] : 0;
$end = array_key_exists('end', $form->cleanedData) ? $form->cleanedData['end'] : null;
$name = array_key_exists('name', $form->cleanedData) ? $form->cleanedData['name'] : "";
$length = array_key_exists('length', $form->cleanedData) ? $form->cleanedData['length'] : null;
$debug = array_key_exists('debug', $form->cleanedData) ? $form->cleanedData['debug'] : false;


if($length !== null)
{
    $result = substr ($text, $start, $length);
}elseif($end)
{
    $length = strlen($text) - $start - $end;
    if($length < 0)
        $length = 0;

    $result = substr ($text, $start, $length);
}else
{
    $result = substr ($text, $start);
}

if($name)
    $doc->push(trim($name), $result);

$doc->push("result", $result);
$doc->push("length", strlen($result));

if($debug)
{
    $doc->push("input-name", $name);
    $doc->push("input-start", $start);
    $doc->push("input-end", $end);
    $doc->push("input-length", $length);    
}

$doc->send();
