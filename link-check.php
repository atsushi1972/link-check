<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Insert title here</title>
</head>
<body>
	<form name="form1" action="link-check.php" method="post">
	  <input type="text" name="url" value="<?=$_POST['url']?>">
	  <input type="submit" value="送信">　
	</form>
</body>
</html>

<?php
require_once 'PEAR.php';
require_once 'HTTP/Request2.php';

set_time_limit (0);

function getHttpRequest($url)
{
	$options = array(
		'connect_timeout'=>10, 	//Connection timeout in seconds. Exception will be thrown if connecting to remote host takes more than this number of seconds. 	integer 	10
		'follow_redirects' => true,
		'ssl_verify_peer'=> false,
	);

	try {
		$req = new HTTP_Request2($url);
		$req->setConfig($options);

		$response = $req->send();
		if (!PEAR::isError($response)) {
			$result['status'] =$response->getStatus();
			$result['reason'] = $response->getReasonPhrase();
			$result['version'] = $response->getVersion();
			$result['headers'] = $response->getHeader();
			$result['cookies'] = $response->getCookies();
			$result['contents'] = $response->getBody();

			$encoding = mb_detect_encoding($result['contents'], "UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP ", true);
			//			print_r($encoding);
			$result['contents_utf8'] = mb_convert_encoding($result['contents'],"UTF-8",$encoding);
			//			print_r($contents_utf8);
		}

	} catch (HTTP_Request2_Exception $e) {
		$result['error'] = true;
		$result['error_message'] = $e->getMessage();
	} catch (Exception $e) {
		$result['error'] = true;
		$result['error_message'] = $e->getMessage();
	}

	return $result;
}

function is_url($text) {
	if (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $text)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function createUri( $base = '', $relational_path = '' ) {
	
	$parse = array (
		'scheme' => null,
		'user' => null,
		'pass' => null,
		'host' => null,
		'port' => null,
		'path' => null,
		'query' => null,
		'fragment' => null,
	);
	$parse = parse_url ( $base );
	
	// パス末尾が / で終わるパターン
	if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== FALSE ) {
		$parse['path'] .= '.';	// ダミー挿入
	}
	if ( preg_match ( '#^https?\://#', $relational_path ) ) {
		// 相対パスがURLで指定された場合
		return $rel_path;
	} elseif ( preg_match ( '#^/.*$#', $relational_path ) ) {
		// ドキュメントルート指定
		return $parse['scheme'] . '://' . $parse ['host'] . $relational_path;
	} else {
		// 相対パス処理
		$basePath = explode ( '/', dirname ( $parse ['path'] ) );
		$relPath = explode ( '/', $relational_path );
		foreach ( $relPath as $relDirName ) {
			if ($relDirName == '.') {
				array_shift ( $basePath );
				array_unshift ( $basePath, '' );
			} elseif ($relDirName == '..') {
				array_pop ( $basePath );
				if ( count ( $basePath ) == 0 ) {
					$basePath = array( '' );
				}
			} else {
				array_push ( $basePath, $relDirName );
			}
		}
		$path = implode ( '/', $basePath );
		return $parse ['scheme'] . '://' . $parse ['host'] . $path;
	}
}


if(empty($_POST['url'])) die;

echo "{$_POST['url']}<br/>\r\n";

//HTTPリクエスト
//$parent_url = "http://www.google.co.jp/search?client=ubuntu&channel=fs&q=php+url+%E6%8A%BD%E5%87%BA&ie=utf-8&oe=utf-8&hl=ja";
$parent_url = $_POST['url'];
//die(print_r(parse_url($parent_url),false));
// $position = strrpos($parent_url,'/');
// if(substr($parent_url,$position-1,1)!="/"){
// 	$parent_url_path = substr($parent_url,0,$position+1);
// } else {
// 	$parent_url_path = $parent_url."/";
// }
// echo "url:{$parent_url_path}<br/>\r\n";
echo "url:".createUri($parent_url)."<br/>\r\n";

if(!is_url($parent_url)) die("Invalid url:".$parent_url);

$result = getHttpRequest($parent_url);
if(array_key_exists('error', $result)) die($result['error_message']);

preg_match_all("/<a href=\"(.+?)\"(.+?)>(.+?)<\/a>/misu",$result['contents_utf8'],$matches);

$start = time();
echo "start:".$start."<br/>\r\n";
echo "count:".count($matches[1])."<br/>\r\n";

//リンク先URL処理ループ
foreach($matches[1] as $url)
{
	if(!is_url($url)) $url = createUri($parent_url,$url);

//	print_r($url."\r\n");
	$result = getHttpRequest($url);
	if(array_key_exists('error', $result)) die($result['error_message']);
 	if($result['status']!=200) echo $url.','.$result['status']."<br/>\r\n";
//	echo $url.','.$result['status']."<br/>\r\n";
}

$end= time();
echo "end:".$end."<br/>\r\n";
$duration=$end-$start;
echo "duration:".$duration."<br/>\r\n";

?>
