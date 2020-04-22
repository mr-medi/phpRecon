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
            $enlace = $link->getAttribute('href');
            /*
            Prevent from adding the same page because  of #
            ex:http://mipage.com/1#whatever
             */
            $isSamePage = substr($enlace, 0, 1) == '#';

            //IF THE ROUTE IS RELATIVE...
            if(!self::is_absolute($enlace))
            {
                //IS THE LINK IN THE ROOT DIRECTORY ?
                if(substr($enlace, 0, 1) == '/')
                    $enlace = $this->host.$enlace;
                else
                    $enlace = $this->url.$enlace;
            }

            //print_r(parse_url($enlace));
            //echo $enlace."<br>";
            $protocol = parse_url($enlace)['scheme'];
            $host = parse_url($enlace)['host'];
            //SI ES UN ENLACE DEL MISMO DOMINIO...
            if(!in_array($enlace, $this->pages) && strpos($protocol."://".$host, parse_url($this->host)['host']) !== false && !$isSamePage)
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
                    $enlace = $link->getAttribute('href');
                    /*
                    Prevent from adding the same page because  of #
                    ex:http://mipage.com/1#
                     */
                    $isSamePage = substr($enlace,0,1) == '#';

                    //IF THE ROUTE IS RELATIVE...
                    if(!self::is_absolute($enlace))
                    {
                        //IS THE LINK IN THE ROOT DIRECTORY ?
                        if(substr($enlace, 0, 1) == '/')
                            $enlace = $this->host.$enlace;
                        elseif(substr($enlace, 0, 2) == './')
                            $enlace = $response['url'].substr($enlace,2);
                        else
                            $enlace = $this->url.$enlace;
                    }
                    //echo $enlace." - ".$response['url']."<br>";
                    //echo $enlace."<br>";
                    if(!in_array($enlace, $this->pages) && !$isSamePage)
                    {
                        $protocol = parse_url($enlace)['scheme'];
                        $host = parse_url($enlace)['host'];
                        $urlLink = $protocol."://".$host;
                        $urlDomain = parse_url($this->host)['scheme']."://".parse_url($this->host)['host'];
                        $isOk = strpos($protocol."://".$hostDomain,parse_url($this->host)['scheme']."://".parse_url($this->host)['host']);
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
                        $result .= "<br><strong><span style='color:green'> + </span>Comentario: </strong><br><br>";
                        $result .= $comment."<br>";
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
                    $result .= "<div name='forms' class='center-text'>";
                    $result .= "<strong><span style='color:green'> + </span>Formu: </strong><br><br>";
                    $result .= "<strong>action: </strong>".$action."<br>";
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
