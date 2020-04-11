<?php
class Page
{
    private $url;
    private $htmlComments;

    public function __construct($url)
    {
        $this->url = $url;
        $this->$htmlComments = array();
    }

    public function getURL()
    {
        return $this->url;
    }
}
