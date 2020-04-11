<?php
class Domain
{
    /*
    URL de la pagina principal del dominio,
    alude al fichero index
     */
    private $url;
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
            //SI
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
            foreach ($headers as $key => $value) {
                echo "<strong>$key : </strong>".$value."<br>";
            }
            $html = $response['html'];
            //echo $html;
        }
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');
        foreach($links as $link)
        {
            $enlace = $link->getAttribute('href');
            if(!self::is_absolute($enlace))
                $enlace = $this->url.$enlace;

            $protocol = parse_url($enlace)['scheme'];
            $host = parse_url($enlace)['host'];
            //SI ES UN ENLACE DEL MISMO DOMINIO...
            //var_dump(strpos($enlace,$protocol."://".$host));
            if(strpos($enlace,$protocol."://".$host) !== false)
            {
                self::addPage($enlace);
            }
        }
        //OBTENIENDO ENLACES DE CADA ENLACE DE LA PAG PRINCIPAL
        $r = new Request($this->pages);
        $responses = $r->doGetRequests();
        echo "<pre>: ";
        foreach($responses as $response)
        {
            $headers = $response['headers'];
            $html = $response['html'];
            //echo $response['url']."ALLA: ".$html;
            $dom = new DomDocument();
            $dom->loadHTML($html);
            $links = $dom->getElementsByTagName('a');
            foreach($links as $link)
            {
                $enlace = $link->getAttribute('href');
                //print_r(parse_url($enlace));
                if(!self::is_absolute($enlace))
                    $enlace = $this->url.$enlace;
                if(!in_array($enlace, $this->pages))
                {
                    //print_r(parse_url($enlace))."<BR>";
                    $protocol = parse_url($enlace)['scheme'];
                    $host = parse_url($enlace)['host'];
                    $hostDomain = parse_url($this->url)['host'];
                    //SI ES UN ENLACE DEL MISMO DOMINIO...
                    if(strpos($hostDomain,$host) !== false)
                    {
                        self::addPage($enlace);
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
            $headers = $response['headers'];
            $html = $response['html'];
            //header('content-type:text/plain');
            //echo $html;
            //echo $response['url']."<br>";
            $dom = new DomDocument();
            $dom->loadHTML($html);
            $body = $dom->getElementsByTagName('body');
            $body = $body->item(0);
            $comments = [];
            //GET COMMENTS
            foreach($body->childNodes as $node)
            {
                if($node->nodeType == 8)
                {
                     $this->comments[] = $node->nodeValue;
                     $comments[] = $node->nodeValue;
                }
            }
            //GET FORMUS
            $formus = [];
            $forms = $dom->getElementsByTagName('form');
            foreach($forms as $f)
            {
                $this->forms[] = $f;
                $formus[] = $f;
            }
            $this->data[] =
            ['url'=>$response['url'],
            'comments'=>$comments,
            'forms'=>$formus,
            'headers'=>$headers];
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
}
