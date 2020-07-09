<?php

/**
 * A php library to make HTTP Requests using curl with threading
 * and get all the HTTP response headers parsed and body
 *
 * @author Mr.medi <https://github.com/mr-medi>
 * @version 1.0
 */
class Request
{
    private $urls;
    private $params;
    private $headersRequest;
    private $headersResponse;
    private $response;

    /**
     * Constructor of the request
     * @param array $urls
     */
    public function __construct($urls)
    {
        $this->urls = $urls;
        $this->headersRequest = array();
        $this->headersResponse = array();
        $this->params = array();
    }

    /**
     * DO HTTP GET request using multi threading of
     * PHP-CURL
     * @return array Info of the HTTP requests
     */
    public function doGetRequests()
    {
        $dataURLS = array();
        $rolling_window = 100;
        $rolling_window = (count($this->urls) < $rolling_window) ? count($this->urls) : $rolling_window;
        $master = curl_multi_init();
        $curl_arr = array();
        $std_options = array(
            //CURLOPT_REFERER => $this->url,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.83 Safari/537.1');
        $options = $std_options;
        for ($i = 0; $i < $rolling_window; $i++)
        {
          $ch = curl_init();
          $options[CURLOPT_URL] = $this->urls[$i];
          curl_setopt_array($ch,$options);
          curl_multi_add_handle($master, $ch);
        }
        do
        {
          while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
          if($execrun != CURLM_OK)
              break;
         // a request was just completed -- find out which one
          while($done = curl_multi_info_read($master))
          {
              $info = curl_getinfo($done['handle']);//HEADERS REQUEST
              $output = curl_multi_getcontent($done['handle']);//RESPONSE HTML

              if(isset($this->urls[$i + 1]))
              {
                  // start a new request (it's important to do this before removing the old one)
                  $ch = curl_init();
                  $options[CURLOPT_URL] = $this->urls[$i++];// increment i
                  curl_setopt_array($ch,$options);
                  curl_multi_add_handle($master, $ch);
              }
              $headerSize = curl_getinfo($done['handle'], CURLINFO_HEADER_SIZE);
              $header = substr($output, 0, $headerSize);
              $header = self::getHeaders($header);
              // extract body
              $body = substr($output, $headerSize);
              $dataURLS[] = ['url'=>$info['url'],'html'=>$body,'headers'=>$header,'http_code'=>$info['http_code']];
              // remove the curl handle that just completed
              curl_multi_remove_handle($master, $done['handle']);
          }
        }while ($running);
        curl_multi_close($master);
        return $dataURLS;
    }

    /**
     * DO HTTP POST request using multi threading of
     * PHP-CURL
     * @param array $urls
     * @return array info of the HTTP POST request
     */
    public function doPostRequests($urls)
    {
        $dataURLS = array();
        $rolling_window = 100;
        $rolling_window = (count($urls) < $rolling_window) ? count($urls) : $rolling_window;
        $master = curl_multi_init();
        $curl_arr = array();
        $std_options = array(
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.83 Safari/537.1');
        $options = $std_options;
        for ($i = 0; $i < $rolling_window; $i++)
        {
            $url = $urls[$i]['url'];
            $params = "";
            foreach ($urls[$i]['params'] as $p)
                $params .= $p['name'].'='.$p['value'].'&';
            $params = rtrim($params, '&');

            $ch = curl_init();
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POSTFIELDS] = $params;
            curl_setopt_array($ch,$options);
            curl_multi_add_handle($master, $ch);
        }
        $index = 0;
        do
        {
          while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
          if($execrun != CURLM_OK)
              break;
         // a request was just completed -- find out which one
          while($done = curl_multi_info_read($master))
          {
              $info = curl_getinfo($done['handle']);//HEADERS REQUEST
              $output = curl_multi_getcontent($done['handle']);//RESPONSE HTML
              if($info['http_code'] == 302)
                $p = $urls[$index]['params'];
              // start a new request (it's important to do this before removing the old one)
              if(isset($urls[$i + 1]))
              {
                  $ch = curl_init();
                  $i++;
                  $index++;
                  $options[CURLOPT_URL] = $url;
                  $params = "";
                  foreach ($urls[$i]['params'] as $p)
                      $params .= $p['name'].'='.$p['value'].'&';
                  $params = rtrim($params, '&');
                  echo $params;
                  $options[CURLOPT_POSTFIELDS] = $params;
                  curl_setopt_array($ch,$options);
                  curl_multi_add_handle($master, $ch);
              }
              $headerSize = curl_getinfo($done['handle'], CURLINFO_HEADER_SIZE);
              $header = substr($output, 0, $headerSize);
              $header = self::getHeaders($header);
              //extract body
              $body = substr($output, $headerSize);
              $dataURLS[] = ['url'=>$info['url'], 'html'=>$body, 'headers'=>$header, 'http_code'=>$info['http_code'], 'params'=>$params];
              // remove the curl handle that just completed
              curl_multi_remove_handle($master, $done['handle']);
          }
        }while ($running);
        curl_multi_close($master);
        return $p;
    }

    /**
     * Get all the headers parsed given a raw string
     * @param str $respHeaders
     * @return array HTTP headers
     */
    public function getHeaders($respHeaders)
    {
        $headers = array();
        $headerText = substr($respHeaders, 0, strpos($respHeaders, "\r\n\r\n"));
        foreach (explode("\r\n", $headerText) as $i => $line)
        {
            if ($i === 0)
            {
                $headers['http_code'] = $line;
            }
            else
            {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Add a HTTP parameter to the request
     * @param str $param HTTP parameter name
     * @param str $value HTTP parameter value
     */
    public function addParam($param , $value)
    {
        $this->params[] = ['param'=>$param, 'value'=>$value];
    }

    /**
     *
     * @param array $words
     * @param array $params [description]
     * @param str $action URL that means where send the data
     * @return str
     */
    public static function doBruter($words, $params, $action)
    {
        $urls = [];
        foreach($words as $word)
        {
            $paramsRequest = [];
            foreach($params as $param)
            {
                $name = $param['name'];
                $value = $param['value'];
                $type = $param['type'];
                //ADDING PARAMS
                if($value != "")
                {
                    $paramsRequest[] = ['name'=>$name,'value'=>$value,'type'=>$type];
                }
                else
                {
                    if($name == 'mail' || $type == "email" || $name =="email")
                    {
                        $paramsRequest[] = ['name'=>$name,'value'=>'a@a','type'=>$type];
                    }
                    elseif(strtolower($type) == 'password' || $name == 'password')
                    {
                        $paramsRequest[] = ['name'=>$name,'value'=>$word,'type'=>$type];
                    }
                    else
                    {
                        $paramsRequest[] = ['name'=>$name,'value'=>'admin','type'=>$type];
                    }
                }
            }
            if($word != "")
                $urls[] = ['url'=>$action, 'params'=>$paramsRequest];
        }

        $r = new Request([$action]);
        $responses = $r->doPostRequests($urls);
        $pass = false;
        foreach($responses as $response)
        {
            if($response['type'] == 'password')
            {
                $pass = true;
                return "PASSWORD FOUND! => ".$response['value'];
            }
        }
        return !$pass ? "Sorry, not found :(" : "";
    }
}
