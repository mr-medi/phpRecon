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

    private $comments;
    private $forms;
    private $responses;
    private $data;

    public function __construct($url)
    {
        $this->url = $url;
        $this->pages = array();
        $this->responses = '';
        $this->comments = array();
        $this->forms = array();
        $this->data = array();
        $this->host = parse_url($this->url)['scheme']."://".parse_url($this->url)['host']."/";
    }

    public function getDataScan()
    {
        $data = array();
        //OBTIENE TODOS LOS LINKS DE LA PAGINA PRINCIPAL Y DEL MISMO DOMINIO
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

    public function getLinks()
    {
        $r = new Request([$this->url]);
        $this->responses = $r->doGetRequests();
        self::addPage($this->url);

        foreach($this->responses as $response)
        {
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
            //echo $enlace."<br>";
            $isMailLink = substr($enlace, 0, 7) == 'mailto:';
            /*
            Prevent from adding the same page because  of #
            ex:http://mipage.com/1#whatever
             */
            $isSamePage = substr($enlace, 0, 1) == '#';
            //IF THE ROUTE IS RELATIVE...
            if(!self::is_absolute($enlace))
            {
                if(substr($enlace, 0, 2) == '//')
                {
                    $protocol = parse_url($this->host)['scheme'];
                    $enlace = $protocol.":".$enlace;
                }
                //IS THE LINK IN THE ROOT DIRECTORY ?
                elseif(substr($enlace, 0, 1) == '/')
                    $enlace = $this->host.substr($enlace,1);
                elseif(substr($enlace, 0, 3) == '../')
                    $enlace = '';
                elseif(substr($enlace, 0, 2) == './')
                    $enlace = $response['url'].substr($enlace, 2);
                elseif(substr($enlace, 0, 1) == '?')
                {
                    echo $pathLink."<BR>";
                    $enlace = $pathLink.$enlace;
                }
                else
                    $enlace = $this->url.$enlace;
            }

            //print_r(parse_url($enlace));
            //echo $enlace."<br>";
            $protocol = parse_url($enlace)['scheme'];
            $host = parse_url($enlace)['host'];
            //SI ES UN ENLACE DEL MISMO DOMINIO...
            if(!$isMailLink && !in_array($enlace, $this->pages) && strpos($protocol."://".$host, parse_url($this->host)['host']) !== false && !$isSamePage)
            {
                //echo "<h2>$enlace</h2><br>";
                self::addPage($enlace);
            }
        }
        //OBTENIENDO ENLACES DE CADA ENLACE DE LA PAG PRINCIPAL
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();

        foreach($responses as $response)
        {
            $headers = $response['headers'];
            //echo $response['url']." - ".$response['http_code']."<br>";
            $html = $response['html'];
            //echo $response['url']."ALLA: ".$html;
            if($response['http_code'] == 200)
            {
                $dom = new DomDocument();
                $dom->loadHTML($html);
                $links = $dom->getElementsByTagName('a');
                foreach($links as $link)
                {
                    $pathLink = parse_url($response['url'])['scheme']."://".parse_url($response['url'])['host'].parse_url($response['url'])['path'];
                    $enlace = $link->getAttribute('href');
                    $isMailLink = substr($enlace, 0, 7) == 'mailto:';
                    /*
                    Prevent from adding the same page because  of #
                    ex:http://mipage.com/1#
                     */
                    $isSamePage = substr($enlace,0,1) == '#';
                    //echo $enlace."<br>";
                    //IF THE ROUTE IS RELATIVE...
                    if(!self::is_absolute($enlace))
                    {
                        if(substr($enlace, 0, 2) == '//')
                        {
                            $protocol = parse_url($this->host)['scheme'];
                            $enlace = $protocol.":".$enlace;
                        }
                        //IS THE LINK IN THE ROOT DIRECTORY ?
                        elseif(substr($enlace, 0, 1) == '/')
                            $enlace = $this->host.substr($enlace,1);
                        elseif(substr($enlace, 0, 3) == '../')
                            $enlace = '';
                        elseif(substr($enlace, 0, 2) == './')
                            $enlace = $response['url'].substr($enlace, 2);
                        elseif(substr($enlace, 0, 1) == '?')
                        {
                            //echo "<h2>$enlace</h2>";
                            //print_r(parse_url($response['url']));
                            $path = parse_url($response['url'])['path'];
                            //echo $this->host." . ".$path."<br>";
                            $enlace = $this->host.$path.$enlace;
                            //echo "RES::".$enlace."<br>";
                        }
                        else
                        {
                            $enlace = $this->host.$enlace;
                            //echo $pathLink."<BR>";
                        }
                    }


                    if(!in_array($enlace, $this->pages) && !$isSamePage && !$isMailLink)
                    {
                        //echo $enlace." - ".$response['url']."<br>";
                        $protocol = parse_url($enlace)['scheme'];
                        $host = parse_url($enlace)['host'];
                        $urlLink = $protocol."://".$host;
                        $urlDomain = parse_url($this->host)['scheme']."://".parse_url($this->host)['host'];
                        //echo $urlLink." - ".$urlDomain."<strong>".var_dump($isOk)."</strong><br>";
                        //SI ES UN ENLACE DEL MISMO DOMINIO...
                        if(strpos($urlLink, $urlDomain) !== false)
                        {
                            //echo $enlace." - ".$response['http_code']."<br>";
                            self::addPage($enlace);
                        }
                    }
                }
            }
        }
    }

    public function getComments()
    {
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();

        foreach($responses as $response)
        {
            //echo $response['url']."<br>";
            $this->comments = [];
            $headers = $response['headers'];
            $html = $response['html'];
            $dom = new DomDocument();
            $dom->loadHTML($html);
            self::showDOMNode($dom);
            //GET FORMUS
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
                        $result .= "<br><div style='margin-left:30px'><h5><strong><span style='color:#e68a00;'>[ * ]</span>Comment: </strong></h5><br><br>";
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
        $list = file_get_contents('dics/rockyou.txt');
        $words = explode("\n" , $list);
        $i = 0;
        $result = "";
        foreach($this->data as $data)
        {
            $urlIterator = $data['url'];
            $forms = $data['forms'];
            if($url == $urlIterator)
            {
                foreach ($forms as $f)
                {
                    $action = $url;
                    $result .= "<div name='forms' class='center-text' style='margin-left:30px'>";
                    $result .= "<h5><strong><span style='color:#e68a00'>[ * ]</span>Form: </strong></h5><br><br>";
                    $result .= "<div style='margin-left:40px;'><strong>action: </strong>".$action."<br>";
                    $result .= "<strong>method: </strong>".$f->getAttribute('method')."<br>";
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
                            $params[] = ['name'=>$name, 'value'=>$value];
                        }
                        else
                        {
                            if($name == 'mail' || $type == "email" || $name =="email")
                            {
                                $params[] = ['name'=>$name, 'value'=>'a@a.com'];
                            }
                            elseif($type == 'password')
                            {
                                $params[] = ['name'=>$name, 'value'=>$words[$i]];
                            }
                            else
                            {
                                $params[] = ['name'=>$name, 'value'=>'admin'];
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
