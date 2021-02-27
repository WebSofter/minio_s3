<?php
// Include the SDK using the Composer autoloader
date_default_timezone_set('Europe/Moscow');
require 'vendor/autoload.php';

$s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => 'russian',
        'endpoint' => 'http://localhost:9000',
        'use_path_style_endpoint' => true,
        'credentials' => [
                'key'    => 'access123',
                'secret' => 'secret123',
            ],
]);
$json = file_get_contents("https://api.exchangeratesapi.io/latest?base=RUB");
$data = json_decode($json);
$bucket = 'prices';
$k = $data->rates->USD;//Стоимость доллара в коэфииенте
$objects = $s3->getIterator('ListObjects', [
     'Bucket' => $bucket,
     // 'Prefix' => $prefix,
     ]);
    
     foreach ($objects as $object) {
          // Выгружаем добавленный контент.
          $retrive = $s3->getObject([
               'Bucket' => $bucket,
               'Key'    => $object['Key']
          ]);
          echo "\nПодготовка файла: ".$object['Key']."\n";
          $body = $retrive['Body'];
          $n = 0;
          $text = "";
          $lines = explode("\n", $body);
          foreach($lines as $line) {
               $line = preg_replace('#[^\w()/.,%\-&]#',"",$line);
               $cols = explode(",", $line);
               $v = round(floatval($cols[2]) * floatval($k), 2);
               if(count($cols) < 4){
                    if($n < 1){
                         $cols[] = "USD";
                    }else{
                         $cols[] = $v;
                    }
               }else{
                    if($n > 0){
                        $cols[3] = $v; 
                    }
               }
               //
               $n++;
               //
               $end = ($n != count($lines) ? "\n" : "");
               //echo "\n".count($lines)."==".$n."\n";
               $text .= implode(",", $cols).$end;
          }
          //--------------------------------------
          //$s3->deleteObject(['Bucket' => 'prices', 'Key' => $object['Key']]);

          $insert = $s3->putObject([
               'Bucket' => $bucket,
               'Key'    => $object['Key'],
               'Body'   => $text
          ]);

          echo $retrive['Body'];

     }

/*
// Сохраняем прайсы в букете prices
$insert = $s3->putObject([
     'Bucket' => 'prices',
     'Key'    => 'test',
     'Body'   => 'Hello from MinIO!!'
]);

// Выгружаем добавленный контент.
$retrive = $s3->getObject([
     'Bucket' => 'prices',
     'Key'    => 'test',
     'SaveAs' => 'testkey_local'
]);

// Выводим результат
echo $retrive['Body'];
*/
