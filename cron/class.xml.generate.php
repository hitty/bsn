<?
class generateXml{
    
    public $item = 0;
    public $currentitem = '';
    public $subitem = '';
    public $checkitem = '';
    
    public $itemContent = array();

    public function __construct()
    {
        global $db, $xmlUrlset, $xml;
        $this->db=& $db;
        $this->xmlUrlset=& $xmlUrlset;
        $this->xml=& $xml;
        
    }
    
    public function append($child = false, $nodeText = false, $sub = false)
    {
        if($child == false) {
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('offer'));
            $this->itemContent[0] = array('offer' => array($this->currentitem) );
        }
        elseif($sub > 0) {
            $key = array_keys($this->itemContent[($sub-1)]);
            $current = $this->itemContent[($sub-1)][$key[0]];
            $current = $current[0];
            $this->currentitem = $current->appendChild($this->xml->createElement($child));
            $this->itemContent[$sub] = array($child => array($this->currentitem) );
            
        }
        else
        {
        }
        
        if($nodeText!=false) $this->setNode($nodeText);
    }

    /* Create node Text */
    public function setNode($nodeText = false)
    {
        if($nodeText!=false) $this->currentitem -> appendChild($this->xml ->createTextNode(htmlspecialchars($nodeText)));
    }

    public function attr($title, $value)
    {
        $this->currentitem -> setAttribute($title, htmlspecialchars($value));
    }

}
?>