<?php
    require_once __DIR__."/src/fuzzer/Domain.class.php";
    require_once __DIR__."/src/fuzzer/Request.class.php";
    require_once __DIR__."/src/fuzzer/Url.class.php";
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
    <body class="text-center" style="background-color: #fdfcd0">
        <!--Definiendo el header -->
        <div style="background-color: #270a29">
        <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
              <header class="masthead mb-auto">
                <div class="inner">
                  <nav class="btn nav nav-masthead justify-content-center">
                    <button style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid #39e5dd; padding: 2px" id="index">Index</button>
                    <button style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid #39e5dd; margin-left: 10px; padding: 2px" id ='comment'>Comments</button>
                    <button style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid #39e5dd;margin-left: 10px;padding: 2px" id='form'>Forms</button>
                    <button style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid #39e5dd;margin-left: 10px;padding: 2px" id='header'>Headers</button>
                    <button style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid #39e5dd;margin-left: 10px;padding: 2px" id='bruter'>Bruter</button>
                  </nav>
                </div>
            </header>
         </div>
           <!--Fin header -->
           <main role="main" style="">
              <img src="assets/images/logo.png" style="margin: 15px; background-color: #54175; border-bottom: 4px solid #c6472c">
               <section class="jumbotron text-center" style="background-color: #fdfcd0">
                   <div class="container" style="">
                       <form action="" method="POST" class="">
                           <img src="assets/images/domain.png" width="400" height="50" style="margin: 10px">
                           <p>
                                <input type="text" class="input-center" placeholder="http://www.mypage.com" name="domain" autofocus required><br>
                                <input type="submit" value="enviar" name="enviar" style="background-color: #e10000; color: white; font-weight: bold; border-radius: 1em; border: 4px solid black; margin: 10px">
                           </p>
                       </form>
                   </div>
               </section>
           </main>
          </div>
           <div style="background-color: white; border: 5px solid black; padding: 4px; margin-left: 7%; margin-right: 7%">
          <?php
          set_time_limit(0);
          //
          if (isset($_POST['bruter']))
          {
              $list = file_get_contents('dics/rockyou.txt');
              $words = explode("\n", $list);
              $action = htmlspecialchars($_POST['action']);
              $method = 'POST';
              $params = json_decode($_POST['params'], true);
              echo Request::doBruter($words, $params, $action);
          }

          //
          if (isset($_POST['domain']) && isset($_POST['enviar']))
          {

              $domain = htmlspecialchars($_POST['domain']);
              //IF THE URL NOT OK, EXIT THE PROGRAM FLOW
              if (!filter_var($domain, FILTER_VALIDATE_URL))
                  die("<strong style='color:red'>URL NOT VALID!!!</strong>");

              /*
              IF THE URL HAVE A EXTENSION WE DONT ADD '/' AT THE
               END OF THE USER INPUT URL.
               Ex: $domain = http://mypage.com
                   => expected output: $domain = http://mypage.com/
                   but if
                   $domain = http://mypage.com/index.php
                       => expected output: $domain = http://mypage.com/index.php
               */
              $extension = pathinfo(parse_url($domain)['path'], PATHINFO_EXTENSION);
              if ($domain[strlen($domain)-1] != "/" && $extension == "")
                  $domain = $domain.'/';


              echo "<br><br><h2>Results for <strong>$domain: </strong></h2>";
              $d = new Domain($domain);
              $start_time = microtime(true);
              $urls = $d->getDataScan();
              $end_time = microtime(true);
              $execution_time = ($end_time - $start_time);
              //GETTING OUTPUT OF GOOGLE DORKS
              echo $d->getPublicInfo();
              //INFO SCAN
              echo "<div id='resume'>";
              echo "<p>Total enlaces encontrados: <strong>". count($urls) ." </strong></p>";
              echo "<p>Resultados obtenidos en <strong>". round($execution_time, 3) ."</strong> segundos...</p><br>";
              echo "</div>";
              //GETTING CONTENT OF ROBOTS.TXT FILE
              echo $d->getRobotsFile();
              //GETTING OUTPUT OF THE SCAN
              echo $d->getParsedDataScan();
          }
          ?>
        </div>
    </body>
</html>
