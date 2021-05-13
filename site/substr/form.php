<?php

require_once(dirname(__FILE__) . "/../../lib/form_helper.php");

class MyForm extends FormHelper
{
    function __construct() 
    {
		$fields=[
			'name'=> [
	            'label'=> "Name" . '\ :',
	            'type'=> "text",
	            'initial'=> "result",
	            'empty' => true,
	            'desc' => "Nom de la variable de retour pour la réponse à Swithvox.",
	        ],
			'text'=> [
	            'label'=> "Text" . '\ :',
	            'type'=> "text",
	            'empty' => true,
	            'trim' => false,
	            'desc' => "Texte sur lequel on veut extraire la sous chaine.",
	        ],
			'start'=> [
	            'label'=> "Start" . '\ :',
	            'type'=> "integer",
	            'empty' => false,
	            'desc' => "Indice de position de début de la sous-chaine. L'indice de position commence à partir de 0. Une valeur négative est permise.",
	        ],
			'end'=> [
	            'label'=> "End" . '\ :',
	            'type'=> "integer",
	            'empty' => true,
	            'desc' => "Indice position de la fin de la sous-chaine. L'indice de position commence à partir de 0. Ne peut être utilisé conjointement avec la longueur.",
	        ],
			'length'=> [
	            'label'=> "Length" . '\ :',
	            'type'=> "integer",
	            'empty' => true,
	            'desc' => "Longeur de la sous-chaine.",
	        ],
	        'debug'=> [
	            'label'=> "Debug" . '\ :',
	            'type'=> "bool",
	            'empty' => true,
	            'desc' => "Affiche les valeurs d'entrées de la fonction.",
	        ],
		];

        parent::__construct($fields);
    }
}