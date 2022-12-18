<?php
//compile with --enable-pcntl 

function main(){
    set_time_limit (0);

    pcntl_async_signals(true);
    pcntl_signal(SIGPIPE, function (int $signo){
        echo 'Process ' . posix_getpid() . ' "php" received signal SIGPIPE, Broken pipe.';
        exit;
    });

    //ssl
    $context = stream_context_create();
    stream_context_set_option($context, 'ssl', 'local_cert', 'server.pem');
    stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
    stream_context_set_option($context, 'ssl', 'verify_peer', false);
    $server = stream_socket_server('ssl://127.0.0.1:9000', $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);


    // $address = '192.168.1.166';
    // $port = 9005;

    // $sock = socket_create(AF_INET, SOCK_STREAM, 0);

    // socket_bind($sock, $address, $port) or die('could not bind\n');

    // socket_listen($sock);

    $i = 0;
    while(1)
    {
        // $client = socket_accept($sock);
        $client = stream_socket_accept($server);
        // $req = socket_read($client, 1024) or die("failed to read\n");
        $req = fread($client, 1024);
        // echo "{$req}";
        $arrayReq = explode(" ", $req);
        // Print_r($arrayReq);
        
        $arrayReq2 = explode("\n", $req);
        // Print_r($arrayReq2);

        $pid = pcntl_fork();
        if($pid == -1){
            die('could not fork');
        } else if ($pid){
            //parent
        } else {
            //child
            if($arrayReq[0] === "GET"){
                //handle get req
                $url = $arrayReq[1];
                if ($url === '/'){
                    //homepage
                    $headers = "HTTP/1.1 200 OK\nLast-Modified: Tue, 8 Mar 2022 15:53:59 GMT\nContent-type: text/html\n";
                    sendToClient("index.html", $client, $headers);
                    exit(0);
                    posix_kill(getmypid(), SIGTERM);
                } else {
                    $filename = substr($url,1);
                    echo "{$filename}\n";
                    $filename2 = $filename . ".html";
                    echo "{$filename2}\n";
                    if(file_exists($filename) === true){
                        $ext = getFileExtension($filename);
                        $contentType = getContentType($ext);
                        $headers = "HTTP/1.1 200 OK\nLast-Modified: Tue, 8 Mar 2022 15:53:59 GMT\n" . $contentType; 
                        // echo "{$filename}";
                        sendToClient($filename, $client, $headers);
                        // return;
                        exit(0);
                        posix_kill(getmypid(), SIGTERM);
                    } else if(file_exists($filename2) === true){
                        $filename = $filename2;
                        $ext = getFileExtension($filename);
                        $contentType = getContentType($ext);
                        $headers = "HTTP/1.1 200 OK\nLast-Modified: Tue, 8 Mar 2022 15:53:59 GMT\n" . $contentType; 
                        // echo "{$filename}";
                        sendToClient($filename, $client, $headers);
                        // return;
                        exit(0);
                        posix_kill(getmypid(), SIGTERM);
                    } else {
                        $filename = "error404.html";
                        $ext = getFileExtension($filename);
                        $contentType = getContentType($ext);
                        $headers = "HTTP/1.1 200 OK\nLast-Modified: Tue, 8 Mar 2022 15:53:59 GMT\n" . $contentType; 
                        // echo "{$filename}";
                        sendToClient($filename, $client, $headers);
                        // return;
                        exit(0);
                        posix_kill(getmypid(), SIGTERM);
                    }
                }
            } else if ($arrayReq == "POST"){
                //handle post req
            }
        }
        // socket_close($client);
        // posix_kill(getmypid(), SIGTERM);
    }
    // echo 'done';

    socket_close($sock);
}

function sendToClient($filename, $client, $headers){
    $chunksize = 1*(512);
    $buffer = '';
    $cnt = 0;
    $handle = fopen($filename, 'rb');
    $filesize = filesize($filename);
    $headers .= $filesize . "\nConnection: Closed\n\n";
    // echo "{$filesize}";
    if($handle === false){
        return false;
    }
    // socket_write($client, $headers, strlen($headers));
    fwrite($client, $headers, strlen($headers));
    // echo "{$headers}";
    $buffer = fread($handle, $chunksize);
    // $firstwritten = socket_write($client, $buffer, strlen($buffer));
    $firstwritten = fwrite($client, $buffer, strlen($buffer));
    while (!feof($handle)){
        
        $buffer = fread($handle, $chunksize);
        // $written = socket_write($client, $buffer, strlen($buffer));
        $written = fwrite($client, $buffer, strlen($buffer));
        // echo "{$firstwritten}\n";
        // echo "{$written}\n";
        if($firstwritten > $written){
            posix_kill(getmypid(), SIGTERM);
            // pcntl_signal(SIGTERM, "main");
            exit(0);
        }
    }
    $status = fclose($handle);
    // sleep(5);
    // echo 'done';
    // posix_kill(getmypid(), SIGTERM);
}

function getFileExtension($filename){
    $dot = strpos($filename, ".");
    $dot += 1;
    $ext = substr($filename, $dot);
    // echo "{$ext}";
    return $ext;
}

function getContentType($ext){
    if($ext === "js"){
        $contentType = "Content-type: application/javascript\n";
    } else if ($ext === "mp4"){
        $contentType = "Content-type: media/mp4\n";
    }else{
        $contentType = "Content-type: text/html\n";
    }
    return $contentType;
}

main();

?>  