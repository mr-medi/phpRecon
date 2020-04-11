<?php
require_once __DIR__."/src/fuzzer/Domain.class.php";
require_once __DIR__."/src/fuzzer/Page.class.php";
require_once __DIR__."/src/fuzzer/Request.class.php";
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <meta charset="utf-8">
        <title>Recon</title>
    </head>
    <style>
        .center
        {
          margin: auto;
          width: 50%;
          border: 3px solid red;
          padding: 20px;
          margin-left: 20%;
        }

        .input-center
        {
          margin: auto;
          width: 20%;
          border: 3px solid black;
          padding: 20px;
          margin: 40px;
          margin-left: 20%;
        }

        .text-center2
        {
          margin: auto;
          width: 20%;
          margin-left: 20%;
        }

        .center-text
        {
            margin-left: 40px;
        }

    </style>
    <body>
        <!--Definiendo el header -->
      <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <header class="masthead mb-auto">
          <div class="inner">
            <nav class="nav nav-masthead justify-content-center">
              <a class="nav-link active" href="#">Home</a>
              <a class="nav-link" href="#">Map</a>
              <a class="nav-link" href="#">Bruter</a>
              <a class="nav-link" href="#">How to use</a>
            </nav>
          </div>
        </header>
        <form action="" method="POST">
            <h2 class="text-center2">Domain:</h2>
            <input type="text" class="input-center" placeholder="https://www.google.com" name="domain"><br>
            <input type="submit" name="enviar" class="text-center2">
        </form>
        </div>
        <?php
        if(isset($_POST['domain']) && isset($_POST['enviar']))
        {
            $domain = $_POST['domain'];
            $extension = pathinfo($domain, PATHINFO_EXTENSION);
            if($domain[strlen($domain)-1] != "/" && $extension != "")
                $domain = $domain.'/';
            echo "<br><br><h2>Results for $domain: </h2>";
            $domain = new Domain($domain);
            $urls = $domain->getDataScan();
            //$domain->getPublicInfo();
            foreach($urls as $url)
            {
                //print_r($url);
                foreach ($url['headers'] as $key => $value) {
                    //echo $key.": ".$value."<br>";
                }

                $page = $url['url'];
                echo "<br><br><h2><span style='color:blue'>[ - ]</span>".$page."</h2><br>";
                echo "<div class='center-text'>";
                foreach ($url['comments'] as $c)
                {
                    echo "<br><strong><span style='color:green'> + </span>Comentario: </strong><br><br>";
                    echo $c."<br>";
                }
                echo "</div><br>";

                foreach ($url['forms'] as $f)
                {
                    $action = $page;
                    echo "<strong><span style='color:green;margin:20px'> + </span>Formu: </strong><br><br>";
                    echo "<div class='center-text'>";
                    echo "<strong>action: </strong>".$action."<br>";
                    echo "<strong>method: </strong>".$f->getAttribute('method')."<br>";
                    $r = new Request([$action]);
                    foreach($f->getElementsByTagName('input') as $input)
                    {
                        $params = [];
                        echo "<strong><span style='color:red'> * </span>input: </strong><br>";
                        $name = $input->getAttribute('name');
                        $type = $input->getAttribute('type');
                        $value = $input->getAttribute('value');
                        $res = $name.": ".$type;
                        if($value != "")
                        {
                            $res .= " => ".$value;
                            $r->addParam($name, $value);
                        }
                        else
                        {
                            if($name == 'mail' || $type == "email" || $name =="email")
                                $r->addParam($name, 'a@a.com');
                            else
                                $r->addParam($name, 'phpRecon');
                        }
                        $res .= "<br>";
                        echo $res;
                    }

                    if(strtoupper($f->getAttribute('method')) == 'POST')
                    {
                        $responses = $r->doPostRequests();
                        foreach($responses as $response)
                        {
                            $headers = $response['headers'];
                            foreach ($headers as $key => $value) {
                                echo "<strong>$key : </strong>".$value."<br>";
                            }
                            $html = $response['html'];
                            //echo "<br><strong>Response: </strong><br>";
                            //echo $html;
                        }
                    }
                    echo "</div><br>";
                }
            }
        }
        ?>
    </body>
</html>
