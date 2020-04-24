<?php
require_once __DIR__."/src/fuzzer/Domain.class.php";
require_once __DIR__."/src/fuzzer/Page.class.php";
require_once __DIR__."/src/fuzzer/Request.class.php";
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/css/styles.css">
        <script type="text/javascript" src="assets/js/myjs.js"></script>
        <script type="text/javascript" src="assets/js/jquery.js"></script>
        <meta charset="utf-8">
        <title>Recon</title>
    </head>
    <body>
     <!--Definiendo el header -->
      <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <header class="masthead mb-auto">
          <div class="inner">
            <nav class="nav nav-masthead justify-content-center">
              <button class="nav-link active" id="index">Index</button>
              <button class="nav-link" id ='comment' >Comments</button>
              <button class="nav-link" id='form'>Forms</button>
              <button class="nav-link" id='header'>Headers</button>
              <button class="nav-link" id='bruter'>Bruter</button>
            </nav>
          </div>
        </header>
        <!--Fin header -->
        <form action="" method="POST">
            <h2 class="text-center2">Domain:</h2>
            <input type="text" class="input-center" placeholder="https://www.google.com" name="domain" autofocus><br>
            <input type="submit" name="enviar" class="text-center2">
        </form>
        </div>
        <?php
        set_time_limit(0);
        //
        if(isset($_POST['bruter']))
        {
            $list = file_get_contents('dics/rockyou.txt');
            $words = explode("\n" , $list);
            $action = $_POST['action'];
            $method = 'POST';
            $params = json_decode($_POST['params'], true);
            $i = 0;

            foreach($words as $word)
            {
                $r = new Request([$action]);
                foreach($params as $param)
                {
                    $name = $param['name'];
                    $value = $param['value'];
                    echo "<strong>$name : </strong>$value<br>";
                    //ADDING PARAMS
                    if($value != "")
                    {
                        $r->addParam($name, $value);
                    }
                    else
                    {
                        if($name == 'mail' || $type == "email" || $name =="email")
                        {
                            $r->addParam($name, 'a@a.com');
                        }
                        elseif(strtolower($type) == 'password' || $name == 'password')
                        {
                            $r->addParam($name, $word);
                        }
                        else
                        {
                            $r->addParam($name, 'admin');
                        }
                    }
                }
                $responses = $r->doPostRequests();

                foreach($responses as $response)
                {
                    $headers = $response['headers'];
                    foreach ($headers as $param => $value)
                        echo "<strong>$param : </strong>".$value."<br>";
                    $html = $response['html'];
                    echo "<br><strong>Response: </strong><br>";
                    echo $response['http_code']."<br>";
                }
            }
        }
        //
        if(isset($_POST['domain']) && isset($_POST['enviar']))
        {
            $domain = $_POST['domain'];
            if(!filter_var($domain, FILTER_VALIDATE_URL))
            {
                die("URL INVALIDA!");
            }
            $extension = pathinfo(parse_url($domain)['path'], PATHINFO_EXTENSION);
            //echo $extension;
            //echo "<pre>";print_r(parse_url($domain));
            if($domain[strlen($domain)-1] != "/" && $extension == "")
                $domain = $domain.'/';
            echo "<br><br><h2>Results for <strong>$domain: </strong></h2>";
            $domain = new Domain($domain);
            $start_time = microtime(true);
            $urls = $domain->getDataScan();
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            //$domain->getPublicInfo();
            //INFO SCAN
            echo "<div id='resume'>";
            echo "<p>Total enlaces encontrados  : <strong>". count($urls) ." </strong></p>";
            echo "<p>Resultados obtenidos en <strong>".$execution_time."</strong> segundos....</p><br>";
            echo "</div>";
            //FIN SCAN
            foreach($urls as $url)
            {
                $page = $url['url'];
                $totalComments = count($url['comments']);
                $totalForms = count($url['forms']);
                echo "<div name='url'>";
                echo "<span name='totalComments' style='display:none'>$totalComments</span>";
                echo "<span name='totalForms' style='display:none'>$totalForms</span>";
                echo "<br><br><h2><span style='color:blue'>[ - ]</span>".$page."</h2><br>";
                //HEADERS
                echo "<div name='header' class='center-text'>";
                echo "<h3><strong><span style='color:green'>[ + ]</span>Headers: </strong></h3><br><br>";
                foreach ($url['headers'] as $param => $value)
                    echo "<strong style='margin-left:30px'>".$param.": </strong>".$value."<br>";
                echo "</div>";
                //COMMENTS
                echo "<div name='comments' class='center-text'>";
                echo $totalComments > 0 ? "<br><br><h3><strong><span style='color:green'>[ + ]</span>Comments: </strong></h3><br><br>":"";
                echo $domain->getParsedComments($page);
                echo "</div><br>";
                //FORMS
                echo "<div name='forms' class='center-text'>";
                echo $totalForms > 0 ? "<br><br><h3><strong><span style='color:green'>[ + ]</span>Forms: </strong></h3><br><br>":"";
                echo $domain->getParsedForms($page);
                echo "</div></div>";
            }
        }
        ?>
    </body>
</html>
