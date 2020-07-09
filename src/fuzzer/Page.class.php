<?php

/**
 * Class with the logic about the pages
 * NOT IN USE FOR THE MOMENT!
 *
 * @author Mr.Medi <https://github.com/mr-medi>
 * @version 1.0
 */
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
