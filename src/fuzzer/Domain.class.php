<?php

/**
 * Class with the logic to get all HTML comments,
 * forms, headers...
 *
 * @author Mr.Medi <https://github.com/mr-medi>
 * @version 1.0
 */
class Domain
{
    /**
     * URL from user input
     * Ex: http://mypage.com/login.php
     * @var str
     */
    private $url;

    /**
     * Host of the URL
     * Ex: http://mypage.com/
     * @var str
     */
    private $host;

    /**
     * Un dominio tiene paginas asociadas, por ejemplo:
     * login.php, noticias.php...
     * @var array
     */
    private $pages;

    /**
     * HTML comments foreach page
     * @var array
     */
    private $comments;

    /**
     * HTML forms foreach page
     * @var array
     */
    private $forms;

    /**
     * HTTP responses foreach page
     * @var array
     */
    private $responses;

    /**
     * Final results about the scan
     * @var array
     */
    private $data;

    /**
     * Constructor of the Domain
     * @param str $url URL given by the user
     */
    public function __construct($url)
    {
        $this->url = $url;
        $this->pages = array();
        $this->responses = array();
        $this->comments = array();
        $this->forms = array();
        $this->data = array();
        $parser = parse_url($this->url);
        if(isset($parser['port']))
            $this->host = $parser['scheme']."://".$parser['host'].":".$parser['port']."/";
        else
            $this->host = $parser['scheme']."://".$parser['host']."/";
    }

    /**
     * Do the scan and returns the data
     * @return array Final data of the scan
     */
    public function getDataScan()
    {
        //OBTIENE TODOS LOS LINKS DE LA PAGINA PRINCIPAL Y DEL MISMO DOMINIO
        self::getLinks();
        //self::getPublicInfo();
        //OBTIENE TODOS LOS COMENTARIOS Y FORMUS HTML DE CADA PAGINA
        self::getComments();
        return $this->data;
    }

    /**
     * Do a GET request to the robots.txt file on the domain  given.
     * Ex: http://mypage.com/robots.txt
     * @return str
     */
    public function getRobotsFile()
    {
        $r = new Request([$this->host."robots.txt"]);
        $responses = $r->doGetRequests();

        foreach($responses as $response)
        {
            $html = $response['html'];
            $httpCode = $response['http_code'];
        }
        $format = "<br><h2><span style='color:blue'>[ - ]</span> robots.txt Found</h2><br>";
        return $httpCode == 200 ? "$format<pre>".htmlspecialchars($html)."</pre><br>" : 'robots.txt not found...';
    }

    /**
     * Do a Google dork search using the 'site:' dork
     * and add it to the urls array
     * Dork example: site:mydomain.com
     */
    public function getPublicInfo()
    {
        $domain = parse_url($this->url)['host'];
        $query = "site:".$domain;
        $search = "https://www.google.com/search?q=site%3A".$domain;
        $r = new Request([$search]);
        $responses = $r->doGetRequests();
        foreach($responses as $response)
        {
            $headers = $response['headers'];
            $html = $response['html'];
        }
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');
        foreach($links as $link)
        {
            $enlace = $link->getAttribute('href');
            $protocol = parse_url($enlace)['scheme'];
            $host = parse_url($enlace)['host'];
            //SI ES UN ENLACE DEL MISMO DOMINIO...
            if(strpos($this->url,substr($enlace,0))!==false)
                self::addPage($enlace);
        }
    }

    /**
     *
     */
    public function getLinks()
    {
        $r = new Request([$this->url]);
        $res = $r->doGetRequests();
        self::addPage($this->url);

        foreach($res as $response)
        {
            //$this->responses[] = $response;
            $url = $response['url'];
            $headers = $response['headers'];
            $html = $response['html'];
        }
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');

        foreach($links as $link)
        {
            $pathLink = parse_url($response['url'])['scheme']."://".parse_url($response['url'])['host'].parse_url($response['url'])['path'];
            $enlace = $link->getAttribute('href');
            $extension = pathinfo(parse_url($enlace)['path'], PATHINFO_EXTENSION);
            //echo $enlace."<br>";
            $isMailLink = substr($enlace, 0, 7) == 'mailto:';
            /*
            Prevent from adding the same page because  of #
            ex:http://mipage.com/1#whatever
             */
            $isSamePage = substr($enlace, 0, 1) == '#';
            //IF THE ROUTE IS RELATIVE CONVERT IT TO ABSOLUTE
            if(!Url::is_absolute($enlace))
                $enlace = self::getAbsoutePath($enlace, $url, $extension);

            $protocol = parse_url($enlace)['scheme'];
            $host = parse_url($enlace)['host'];
            //SI ES UN ENLACE DEL MISMO DOMINIO...
            if(!$isMailLink && !in_array($enlace, $this->pages) && strpos($protocol."://".$host, parse_url($this->host)['host']) !== false && !$isSamePage)
            {
                self::addPage($enlace);
            }
        }
        //OBTENIENDO ENLACES DE CADA ENLACE DE LA PAG PRINCIPAL
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();

        foreach($responses as $response)
        {
            //$this->responses[] = $response;
            $this->responses[] = $response;
            $headers = $response['headers'];
            $html = $response['html'];
            $dom = new DomDocument();
            $dom->loadHTML($html);
            $links = $dom->getElementsByTagName('a');

            foreach($links as $link)
            {
                $pathLink = parse_url($response['url'])['scheme']."://".parse_url($response['url'])['host'].parse_url($response['url'])['path'];
                $enlace = ltrim($link->getAttribute('href'), '/');
                $extension = pathinfo(parse_url($enlace)['path'], PATHINFO_EXTENSION);
                $isMailLink = substr($enlace, 0, 7) == 'mailto:';
                /*
                Prevent from adding the same page because  of #
                ex:http://mipage.com/1#top
                */
                $isSamePage = substr($enlace,0,1) == '#';
                //IF THE ROUTE IS RELATIVE CONVERT IT TO ABSOLUTE
                if(!Url::is_absolute($enlace))
                    $enlace = self::getAbsoutePath($enlace, $response['url'], $extension);

                if(!in_array($enlace, $this->pages) && !$isSamePage && !$isMailLink)
                {
                    $protocol = parse_url($enlace)['scheme'];
                    $host = parse_url($enlace)['host'];
                    $urlLink = $protocol."://".$host;
                    $urlDomain = parse_url($this->host)['scheme']."://".parse_url($this->host)['host'];
                    //SI ES UN ENLACE DEL MISMO DOMINIO...
                    if(strpos($urlLink, $urlDomain) !== false)
                    {
                        self::addPage($enlace);
                    }
                }
            }
        }
    }

    /**
     *
     * @param str $link
     * @param str $fullURL
     * @param str $extension
     * @return str Absolute URL
     */
    public function getAbsoutePath($link, $fullURL, $extension)
    {
       return Url::parse($fullURL)->join($link);
    }

    /**
     * Get all HTML comments, forms, headers of all the links
     * and add it to the data array
     */
    public function getComments()
    {
        $urlBuffer = [];
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();
        foreach($responses as $response)
        {
            /*
            PREVENT FROM ADDING THE SAME PAGE BECAUSE OF
            HTTP 30X STATUS CODE.
            Ex: the url http://mypage.com/buy redirects
            to http://mypage.com/login in case of not be
            logged in
             */
            if(!in_array($response['url'], $urlBuffer))
            {
                $this->comments = [];
                $headers = $response['headers'];
                $html = $response['html'];
                $dom = new DomDocument();
                $dom->loadHTML($html);
                self::showDOMNode($dom);
                //GET FORMS
                $this->forms = [];
                $forms = $dom->getElementsByTagName('form');
                foreach($forms as $f)
                    $this->forms[] = $f;

                $urlBuffer[] = $response['url'];
                $this->data[] =
                [
                    'url'=>$response['url'],
                    'comments'=>$this->comments,
                    'forms'=>$this->forms,
                    'headers'=>$headers
                ];
            }
        }
    }

    /**
     * [showDOMNode description]
     * @param  [type] $domNode [description]
     */
    public function showDOMNode($domNode)
    {
        foreach ($domNode->childNodes as $node)
        {
            //SI ES UN COMENTARIO..
            if($node->nodeType == 8)
            {
                 $this->comments[] = $node->nodeValue;
            }
            if($node->hasChildNodes())
            {
                self::showDOMNode($node);
            }
        }
    }

    /**
     * Add a page given a url
     * @param str $p URL of the page
     */
    public function addPage($p)
    {
        $this->pages[] = $p;
    }

    /**
     * Return all HTML comments with custom style
     * @return str
     */
    public function getParsedComments($url)
    {
        $result = "";
        foreach($this->data as $data)
        {
            $urlIterator = $data['url'];
            $comments = $data['comments'];
            if($url == $urlIterator)
            {
                $totalComments = count($comments);
                $result .= "<div name='comments' class='center-text'>";
                $result .= $totalComments > 0 ? "<br><br><h3><strong><span style='color:green'>[ + ]</span>Comments: </strong></h3><br><br>":"";

                foreach ($comments as $c)
                {
                    $comment = trim(htmlspecialchars($c));
                    if($comment != "")
                    {
                        $result .= "<br><div style='margin-left:30px'><h5><strong><span style='color:#e68a00;'>[ * ]</span>Comment: </strong></h5><br>";
                        $result .= "".$comment."</div><br>";
                    }
                }
                $result .= "</div>";
            }
        }
        return $result;
    }

    /**
     * Return all HTML forms with custom style
     * @return str
     */
    public function getParsedForms($url)
    {
        $result = "";
        foreach($this->data as $data)
        {
            $urlIterator = $data['url'];
            $forms = $data['forms'];
            if($url == $urlIterator)
            {
                $totalForms = count($forms);
                $result .= "<div name='forms' class='center-text'>";
                $result .= $totalForms > 0 ? "<br><br><h3><strong><span style='color:green'>[ + ]</span>Forms: </strong></h3><br><br>":"";
                foreach ($forms as $f)
                {
                    $onSubmit = $f->getAttribute('onsubmit');
                    $actionAttr = $f->getAttribute('action');
                    if($actionAttr == "")
                        $action = $url;
                    else
                        $action = $actionAttr;
                    //IS THE ACTION ATTRIBUTE A RELATIVE LINK?
                    if(!Url::is_absolute($action))
                        $action = self::getAbsoutePath($action, $url, '');

                    //
                    $result .= "<div name='forms' class='center-text' style='margin-left:30px'>";
                    $result .= "<h5><strong><span style='color:#e68a00'>[ * ]</span>Form: </strong></h5><br>";
                    $result .= "<div style='margin-left:40px;'><strong>action: </strong>".$action."<br>";
                    $result .= "<strong>method: </strong>".$f->getAttribute('method')."<br>";
                    if($onSubmit != "")
                        $result .= "<strong>onSubmit: </strong>".$onSubmit."<br>";
                    $params = [];
                    foreach($f->getElementsByTagName('input') as $input)
                    {
                        $result .= "<strong><span style='color:red'> * </span>input: </strong><br>";
                        $name = $input->getAttribute('name');
                        $type = $input->getAttribute('type');
                        $value = $input->getAttribute('value');
                        $result .= $name.": ".$type;

                        if($value != "")
                        {
                            $result .= " => ".$value;
                            $params[] = ['name'=>$name, 'value'=>$value,'type'=>$type];
                        }
                        else
                        {
                            if($name == 'mail' || $type == "email" || $name =="email")
                            {
                                $params[] = ['name'=>$name, 'value'=>'a@a','type'=>$type];
                            }
                            elseif($type == 'password')
                            {
                                $params[] = ['name'=>'contraseña','type'=>$type];
                            }
                            else
                            {
                                $params[] = ['name'=>$name, 'value'=>'admin','type'=>$type];
                            }
                        }
                        $result .= "<br>";
                    }
                    $result .= "</div><br>";
                    //BRUTER
                    if(strtoupper($f->getAttribute('method')) == 'POST')
                    {
                        $result .= "<div name='bruter'>";
                        $result .= "<form method='POST' action=''>";
                        $result .= "<input type='hidden' name='action' value='$action'>";
                        $result .= "<input type='hidden' name='params' value='".json_encode($params)."'>";
                        $result .= "<input type='submit' name='bruter' value='Do Bruter'>";
                        $result .= "</form></div>";
                    }
                    $result .= "</div><br>";
                }
                $result .= "</div>";
            }
        }
        return $result;
    }

    /**
     * Get all HTTP headers given a url
     * @param str $url URL to parse headers
     * @return str Parsed headers
     */
    public function getParsedHeaders($url)
    {
        $result = "";
        foreach($this->data as $data)
        {
            $urlIterator = $data['url'];
            $headers = $data['headers'];
            if($url == $urlIterator)
            {
                $result .= "<div name='header' class='center-text'>";
                $result .= "<h3><strong><span style='color:green'>[ + ]</span>Headers: </strong></h3><br><br>";
                foreach ($headers as $header => $value)
                    $result .= "<strong style='margin-left:30px'>".$header.": </strong>".$value."<br>";
                $result .= "</div>";
            }
        }
        return $result;
    }

    /**
     * Parse all the info and send it to the
     * index.php file
     * @return str HTML result
     */
    public function getParsedDataScan()
    {
        $result = "";
        foreach($this->data as $data)
        {
            $url = $data['url'];
            $totalComments = count($data['comments']);
            $totalForms = count($data['forms']);
            $result .= "<div name='url'>";
            $result .= "<span name='totalComments' style='display:none'>$totalComments</span>";
            $result .= "<span name='totalForms' style='display:none'>$totalForms</span>";
            $result .= "<br><br><h2><span style='color:blue'>[ - ]</span>".$url."</h2><br>";
            $result .= self::getParsedHeaders($url).self::getParsedComments($url).self::getParsedForms($url);
            $result .= "</div>";
        }
        return $result;
    }
}
