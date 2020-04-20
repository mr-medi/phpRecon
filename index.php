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
              <button class="nav-link" id='bruter'>Bruter</button>
            </nav>
          </div>
        </header>
        <!--Fin header -->
        <form action="" method="POST">
            <h2 class="text-center2">Domain:</h2>
            <input type="text" class="input-center" placeholder="https://www.google.com" name="domain"><br>
            <input type="submit" name="enviar" class="text-center2">
        </form>
        </div>
        <?php
        set_time_limit(0);
        //
        if(isset($_POST['bruter']))
        {
            $action = $_POST['action'];
            $method = 'POST';
            $params = json_decode($_POST['params'], true);
            $r = new Request([$action]);

            foreach($params as $param)
            {
                $name = $param['name'];
                $value = $param['value'];
                echo "<strong>$name : </strong>$value<br>";
                //ADDING PARAMS
                if($value != "")
                {
                    $res .= " => ".$value;
                    $r->addParam($name, $value);
                    $params[] = ['name'=>$name, 'value'=>$value];
                }
                else
                {
                    if($name == 'mail' || $type == "email" || $name =="email")
                    {
                        $r->addParam($name, 'a@a.com');
                        $params[] = ['name'=>$name, 'value'=>'a@a.com'];
                    }
                    else
                    {
                        $r->addParam($name, 'phpRecon');
                        $params[] = ['name'=>$name, 'value'=>'phpRecon'];
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
                echo $html;
            }

        }
        //
        if(isset($_POST['domain']) && isset($_POST['enviar']))
        {
            $domain = $_POST['domain'];
            $extension = pathinfo(parse_url($domain)['path'], PATHINFO_EXTENSION);
            //echo $extension;
            //echo "<pre>";print_r(parse_url($domain));
            if($domain[strlen($domain)-1] != "/" && $extension == "")
                $domain = $domain.'/';
            echo "<br><br><h2>Results for <strong>$domain: </strong></h2>";
            $domain = new Domain($domain);
            $urls = $domain->getDataScan();
            //$domain->getPublicInfo();
            foreach($urls as $url)
            {
                foreach ($url['headers'] as $key => $value) {
                    //echo $key.": ".$value."<br>";
                }
                //print_r($url);
                $page = $url['url'];
                $totalComments = count($url['comments']);
                $totalForms = count($url['forms']);
                //COMMENTS
                echo "<br><br><h2><span style='color:blue'>[ - ]</span>".$page."</h2><br>";
                echo "<div name='comments' class='center-text'>";
                foreach ($url['comments'] as $c)
                {
                    $comment = trim(htmlspecialchars($c));
                    if($comment != "")
                    {
                        echo "<br><strong><span style='color:green'> + </span>Comentario: </strong><br><br>";
                        echo $comment."<br>";
                    }
                }
                echo "</div><br>";
                //FORMS
                foreach ($url['forms'] as $f)
                {
                    $action = $page;
                    echo "<div name='forms' class='center-text'>";
                    echo "<strong><span style='color:green'> + </span>Formu: </strong><br><br>";
                    echo "<strong>action: </strong>".$action."<br>";
                    echo "<strong>method: </strong>".$f->getAttribute('method')."<br>";
                    $params = [];
                    foreach($f->getElementsByTagName('input') as $input)
                    {
                        echo "<strong><span style='color:red'> * </span>input: </strong><br>";
                        $name = $input->getAttribute('name');
                        $type = $input->getAttribute('type');
                        $value = $input->getAttribute('value');
                        $res = $name.": ".$type;
                        if($value != "")
                        {
                            $res .= " => ".$value;
                            $params[] = ['name'=>$name, 'value'=>$value];
                        }
                        else
                        {
                            if($name == 'mail' || $type == "email" || $name =="email")
                                $params[] = ['name'=>$name, 'value'=>'a@a.com'];
                            else
                                $params[] = ['name'=>$name, 'value'=>'phpRecon'];
                        }
                        $res .= "<br>";
                        echo $res;
                    }
                    //BRUTER
                    if(strtoupper($f->getAttribute('method')) == 'POST')
                    {
                        echo "<div name='bruter'>";
                        echo "<form method='POST' action=''>";
                        echo "<input type='hidden' name='action' value='$action'>";
                        echo "<input type='hidden' name='params' value='".json_encode($params)."'>";
                        echo "<input type='submit' name='bruter' value='Do Bruter'>";
                        echo "</form></div>";
                    }
                    echo "</div><br>";
                }
            }
        }
        ?>
    </body>
</html>
