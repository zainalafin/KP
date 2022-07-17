<?php
$token = "1046660311:AAHFQXoEsQpgGY6_prKqNuaXGeovpFLeTsQ"; //Ganti dengan Token yang diperoleh dari BotFather
$usernamebot="@telkom123_bot"; //nama bot yang diperoleh dari BotFather
define('BOT_TOKEN', $token); 

define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

$debug = false;

function exec_curl_request($handle)
{
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);

        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        // do not wat to DDOS server if something goes wrong
    sleep(10);

        return false;
    } elseif ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }

        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successfull: {$response['description']}\n");
        }
        $response = $response['result'];
    }

    return $response;
}

function apiRequest($method, $parameters = null)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");

        return false;
    }

    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");

        return false;
    }

    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
        $val = json_encode($val);
    }
    }
    $url = API_URL.$method.'?'.http_build_query($parameters);

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

    return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");

        return false;
    }

    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");

        return false;
    }

    $parameters['method'] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    return exec_curl_request($handle);
}

// jebakan token, klo ga diisi akan mati
if (strlen(BOT_TOKEN) < 20) {
    die(PHP_EOL."-> -> Token BOT API nya mohon diisi dengan benar!\n");
}

function getUpdates($last_id = null)
{
    $params = [];
    if (!empty($last_id)) {
        $params = ['offset' => $last_id + 1, 'limit' => 1];
    }
  //echo print_r($params, true);
  return apiRequest('getUpdates', $params);
}

// matikan ini jika ingin bot berjalan
//die('baca dengan teliti yak!');

// ----------- pantengin mulai ini
function sendMessage($idpesan, $idchat, $pesan)
{
    $data = [
    'chat_id'             => $idchat,
    'text'                => $pesan,
    'parse_mode'          => 'Markdown',
    'reply_to_message_id' => $idpesan,
  ];

    return apiRequest('sendMessage', $data);
}

function processMessage($message)
{
    global $database;
    if ($GLOBALS['debug']) {
        print_r($message);
    }

    if (isset($message['message'])) {
            
        $sumber = $message['message'];
        $idpesan = $sumber['message_id'];
        $idchat = $sumber['chat']['id'];
        
        $username = $sumber["from"]["username"];
        $nama = $sumber['from']['first_name'];
        $iduser = $sumber['from']['id'];

        if (isset($sumber['text'])) {
            $pesan = $sumber['text'];

            if (preg_match("/^\/view_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/view $cocok[1]";
            }

            if (preg_match("/^\/hapus_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/hapus $cocok[1]";
            }

     // print_r($pesan);

      $pecah2 = explode(' ', $pesan, 3);
            $katake1 = strtolower($pecah2[0]); //untuk command
            $katake2 = strtolower($pecah2[1]); // kata pertama setelah command
            $katake3 = strtolower($pecah2[2]); // kata kedua setelah command
            
      $pecah = explode(' ', $pesan, 2);
            $katapertama = strtolower($pecah[0]); //untuk command
            
        switch ($katapertama) {
        case '/start': 
		case '/start@namabot':
          $text = "Selamat datang..., `$nama`! Untuk bantuan ketik: /help";
          break;

        case '/help': 
        case '/help@namabot':
          $text = "Berikut menu yang tersedia:\n\n";
		  $text .= "/start untuk memulai bot\n";
          $text .= "/help info bantuan ini\n";	 	  
          $text .= "/idteknisi untuk maemasukan id teknisi\n";
          $text .= "/done untuk melaporkan tugas selesai\n";
          
          break; 		      
          
        case '/idteknisi':
        case '/idteknisi@namabot':
            	include "koneksi.php";
        		//if (isset($pecah[1])) {
				//	$idteknisi = $pecah[1];} //mendapatkan id teknisi dari kata kedua
					
        		$tampil="SELECT * FROM teknisi WHERE id_teknisi = '12345aaa'"; 
        		
        		$qryTampil=mysql_query($tampil); 
        		$data=mysql_fetch_array($qryTampil);
				
        		$idteknisi = $data['id_teknisi']; 
				$alamat_cus= $data['alamat_cus'];

                
        		$text = "tiket anda \nid_teknisi: $idteknisi,  \nalamat_cus: $alamat_cus.\n\n";
			//	$text .= "Login http://blogchem.com/registrasi/login.php?username=$username&password=$password"; 
        
        break;         

        default:
          $text = '_maaf kami tidak mengerti?!_';
		  $text .= "\n";
		  $text .= "Klik /help untuk bantuan";
          break;
      }
        } else {
            $text = 'Silahkan tulis pesan yang akan disampaikan..';
			$text .= "\n";
			$text .= "Format: /pesan `pesan`";
        }

        $hasil = sendMessage($idpesan, $idchat, $text);
        if ($GLOBALS['debug']) {
            // hanya nampak saat metode poll dan debug = true;
      echo 'Pesan yang dikirim: '.$text.PHP_EOL;
            print_r($hasil);
        }
    }
}
//$tampil="SELECT * FROM tiket_teknisi WHERE id_teknisi = '12345abcde'"; 
//$qryTampil=mysql_query($tampil); 
//$data=mysql_fetch_array($qryTampil);
//var_dump($tampil);
// pencetakan versi dan info waktu server, berfungsi jika test hook
//echo 'Ver. '.myVERSI.' OK Start!'.PHP_EOL.date('d-m-Y H:i:s').PHP_EOL;

function printUpdates($result)
{
    foreach ($result as $obj) {
        // echo $obj['message']['text'].PHP_EOL;
    processMessage($obj);
        $last_id = $obj['update_id'];
    }

    return $last_id;
}


// AKTIFKAN INI jika menggunakan metode poll
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/*
$last_id = null;
while (true) {
    $result = getUpdates($last_id);
    if (!empty($result)) {
        echo '+';
        $last_id = printUpdates($result);
    } else {
        echo '-';
    }

    sleep(1);
}
*/
// AKTIFKAN INI jika menggunakan metode webhook
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  exit;
} else {
  processMessage($update);
}


?>