<?php
class Domain
{
    /*
    URL de la pagina principal del dominio,
    alude al fichero index
     */
    private $url;
    /*

     */
    private $host;
    /*
    Un dominio tiene paginas asociadas, por ejemplo:
    login.php, noticias.php...
     */
    private $pages;
    /*
    HTML comments
     */
    private $comments;
    /*
    HTML forms of the page
     */
    private $forms;
    /*
    HTTP responses foreach page
     */
    private $responses;
    /*
    Final results about the scan
     */
    private $data;

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

    /*

     */
    public function getDataScan()
    {
        //OBTIENE TODOS LOS LINKS DE LA PAGINA PRINCIPAL Y DEL MISMO DOMINIO
        //self::crawl_page($this->url);
        self::getLinks();
        //self::getPublicInfo();
        //OBTIENE TODOS LOS COMENTARIOS Y FORMUS HTML DE CADA PAGINA
        self::getComments();
        return $this->data;
    }

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

    //
    public function getLinks()
    {
        $r = new Request([$this->url]);
        $res = $r->doGetRequests();
        self::addPage($this->url);

        foreach($res as $response)
        {
            //$this->responses[] = $response;
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
            if(!self::is_absolute($enlace))
                $enlace = self::getAbsoutePath($enlace, $response['url'], $extension);

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
                if(!self::is_absolute($enlace))
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

    public function getAbsoutePath($link, $fullURL, $extension)
    {
        if(substr($link, 0, 2) == '//')
        {
            $protocol = parse_url($this->host)['scheme'];
            $link = $protocol.":".$link;
        }
        //IS THE LINK IN THE ROOT DIRECTORY ?
        elseif(substr($link, 0, 1) == '/')
            $link = $this->host.substr($link,1);
        elseif(substr($link, 0, 3) == '../')
            $link = '';
        elseif(substr($link, 0, 2) == './')
            $link = $fullURL.substr($link, 2);
        elseif(substr($link, 0, 1) == '?')
        {
            $path = parse_url($fullURL)['path'];
            $link = $this->host.$path.$link;
        }
        elseif($extension != "")
        {
            $uri =  parse_url($fullURL, PHP_URL_PATH);
            $pathUrl = ltrim(pathinfo($uri)['dirname'], '/');
            $link = $this->host.$pathUrl."/".$link;
        }
        else
        {
            $link = $this->host.$link;
        }
        return $link;
    }

    public function getComments()
    {
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();
        foreach($responses as $response)
        //foreach($this->responses as $response)
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
            {
                $this->forms[] = $f;
            }
            $this->data[] =
            ['url'=>$response['url'],
            'comments'=>$this->comments,
            'forms'=>$this->forms,
            'headers'=>$headers];
        }
    }

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

    public function addPage($p)
    {
        $this->pages[] = $p;
    }

    public function is_absolute($url)
    {
        $pattern = "/^(?:ftp|https?|feed):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";
        return (bool) preg_match($pattern, $url);
    }

    /*
    Return all comments with custom style
    @return string
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
                foreach ($comments as $c)
                {
                    $comment = trim(htmlspecialchars($c));
                    if($comment != "")
                    {
                        $result .= "<br><div style='margin-left:30px'><h5><strong><span style='color:#e68a00;'>[ * ]</span>Comment: </strong></h5><br>";
                        $result .= "".$comment."</div><br>";
                    }
                }
            }
        }
        return $result;
    }

    /*
    Return all forms with custom style
    @return string
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
                foreach ($forms as $f)
                {
                    $onSubmit = $f->getAttribute('onsubmit');
                    $actionAttr = $f->getAttribute('action');
                    if($actionAttr == "")
                        $action = $url;
                    else
                        $action = $actionAttr;
                    if(!self::is_absolute($action))
                    {
                        if(substr($action, 0, 2) == '//')
                        {
                            $protocol = parse_url($this->host)['scheme'];
                            $action = $protocol.":".$action;
                        }
                        //IS THE LINK IN THE ROOT DIRECTORY ?
                        elseif(substr($action, 0, 1) == '/')
                            $action = $this->host.substr($action,1);
                        elseif(substr($action, 0, 3) == '../')
                            $action = '';
                        elseif(substr($action, 0, 2) == './')
                            $action = $response['url'].substr($action, 2);
                        else
                        {
                            $extension = pathinfo(parse_url($url)['path'], PATHINFO_EXTENSION);
                            if($extension == "")
                                $action = $url.$action;
                            else
                                $action = $url;
                        }
                    }
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
                        //echo $type;
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
            }
        }
        return $result;
    }
}
