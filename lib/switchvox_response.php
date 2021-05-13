<?php

class SwichvoxResponse
{
    protected $encoding;

    private static $xml=<<<XML
<!DOCTYPE ivr_info SYSTEM "http://www.mybz.com/xml/ivr.dtd">
<response>
    <result>
    <ivr_info>
    <variables>
    </variables>
    </ivr_info>
    </result>
</response>
XML;

    protected $xmlDoc;

    function __construct()
    {
        $this->encoding = "ISO-8859-1";

        $this->xmlDoc = new DOMDocument();
        $this->xmlDoc->loadXML(self::$xml);
        $this->xmlDoc->version = "1.0";
        $this->xmlDoc->encoding = $this->encoding;
    }


    protected function search($name)
    {
        $xpath = new DOMXPath($this->xmlDoc);
        $variablesNode = $this->xmlDoc->getElementsByTagName("variables")->item(0);

        return $xpath->query("variable/name[text()='" . trim($name) . "']", $variablesNode);
    }


    public function push($name, $value, $single=true)
    {
        $nodes = $this->search($name);

        if($single && $nodes->length > 0)
        {
            # Update node value
            for($i=0; $i < $nodes->length; $i++)
            {
                $textNode = $nodes->item($i)->parentNode->getElementsByTagName("value")->item(0)->firstChild;
                $textNode->parentNode->replaceChild($this->xmlDoc->createTextNode($value), $textNode);
            }

        } else 
        {
            # Create a new node
            $variableNode = $this->xmlDoc->createElement('variable');
            $nameNode = $this->xmlDoc->createElement('name');
            $valueNode = $this->xmlDoc->createElement('value');

            $nameNode->appendChild($this->xmlDoc->createTextNode(trim($name)));
            $valueNode->appendChild($this->xmlDoc->createTextNode($value));

            $variableNode->appendChild($nameNode);
            $variableNode->appendChild($valueNode);

            $variablesNode = $this->xmlDoc->getElementsByTagName("variables")->item(0);
            $variablesNode->appendChild($variableNode);
        }
    }

    public function pop($name)
    {
        $value = null;

        $variablesNode = $this->xmlDoc->getElementsByTagName("variables")->item(0);
        $nodes = $this->search($name);

        for($i=0; $i < $nodes->length; $i++)
        {
            $variableNode = $nodes->item($i)->parentNode;
            $textNode = $nodes->item($i)->parentNode->getElementsByTagName("value")->item(0)->firstChild;

            if ( $textNode->nodeType === XML_TEXT_NODE ) 
                $value = $textNode->wholeText;

            $variablesNode->removeChild($variableNode);
        }

        return $value;
    }

    public function exists($name)
    {
        $nodes = $this->search($name);
        return (bool) $nodes->length;
    }

    public function send()
    {
        header("Content-type: text/xml; charset={$this->encoding}");
        echo $this->xmlDoc->saveXML();
        exit();
    }
}