<?php
use Workerman\Worker;
require_once './vendor/autoload.php';
require_once '../database.php';

$ws_worker = new Worker("websocket://0.0.0.0:2346");

$ws_worker->count = 4;

$ws_worker->games=[];

$ws_worker->players=[];

function gameExist($id,$list){
    foreach($list as $i => $value){
        if($i==$id){
            echo "true";
            return true;
        }
    }
    echo "false";
    return false;
}

$ws_worker->onConnect = function ($connection) {
    $connection->send(\json_encode(['connected'=>$connection->id]));
};

$ws_worker->onMessage = function ($connection, $data) use ($ws_worker){
    global $ws_worker;
    global $list;
    $dataArray=\json_decode($data);
    $connection->send($data);
    $self=$connection;
    if(isset($dataArray->create) && $dataArray->create){
        $ws_worker->players[$connection->id][]=$dataArray->game_id;
        $ws_worker->games[$dataArray->game_id]=['state'=>0,'creator'=>$connection->id,'word'=>$list[\array_rand($list)],'players'=>[$connection->id=>$dataArray->pseudo]];
        $connection->send(\json_encode(\array_merge(['created'=>true],$ws_worker->games[$dataArray->game_id])));
    }
    if(isset($dataArray->join)){
        if($ws_worker->games[$dataArray->game_id]['state']==0 && gameExist($dataArray->game_id,$ws_worker->games)){
            $ws_worker->players[$connection->id][]=$dataArray->game_id;
            $ws_worker->games[$dataArray->game_id]['players'][$connection->id]=$dataArray->pseudo;
            $connection->send(json_encode(\array_merge($ws_worker->games[$dataArray->game_id],['joined'=>true])));
            foreach($ws_worker->games[$dataArray->game_id]['players'] as $i => $value){
                if($i != $self->id){
                    $connection = $ws_worker->connections[$i];
                    $connection->send(\json_encode(['id'=>$self->id,'joining'=>$ws_worker->games[$dataArray->game_id]['players'][$self->id]]));
                }
            }
        }else{
            $connection->send(\json_encode(['joining'=>false]));
        }
    }
    if(isset($dataArray->start)){
        if($ws_worker->games[$dataArray->game_id]['creator']==$connection->id){
            $ws_worker->games[$dataArray->game_id]['state']=1;
            $ws_worker->games[$dataArray->game_id]['liar']=\array_rand($ws_worker->games[$dataArray->game_id]['players']);
            foreach($ws_worker->games[$dataArray->game_id]['players'] as $i => $value){
                $connection = $ws_worker->connections[$i];
                if($ws_worker->games[$dataArray->game_id]['liar']==$i){
                    $copy=$ws_worker->games[$dataArray->game_id];
                    $copy['word']="Menteur";
                    $connection->send(\json_encode(\array_merge(['starting'=>true],$copy)));
                }
                else{
                    $connection->send(\json_encode(\array_merge(['starting'=>true],$ws_worker->games[$dataArray->game_id])));
                }
            }
        }
    }
    if(isset($dataArray->stop)){
        if($ws_worker->games[$dataArray->game_id]['creator']==$connection->id){
            $ws_worker->games[$dataArray->game_id]['state']=2;
            foreach($ws_worker->games[$dataArray->game_id]['players'] as $i => $value){
                $connection = $ws_worker->connections[$i];
                $connection->send(\json_encode(\array_merge(['stopped'=>true],$ws_worker->games[$dataArray->game_id])));
            }
        }
    }
    if(isset($dataArray->gameExist)){
        if(gameExist($dataArray->gameExist,$ws_worker->games)){$connection->send(\json_encode(["gameExist"=>true]));}else{$connection->send(\json_encode(["gameExist"=>false]));}
    }
};

$ws_worker->onClose = function ($connection) use($ws_worker){
    global $ws_worker;
    $self=$connection;
    if(isset($ws_worker->players[$connection->id])){
        foreach($ws_worker->players[$connection->id] as $i){
            if($connection->id==$ws_worker->games[$i]['creator']){
                foreach($ws_worker->games[$i]['players'] as $key => $value){
                    $connection = $ws_worker->connections[$key];
                    $connection->send(\json_encode(\array_merge(['stopped'=>true],$ws_worker->games[$i])));
                }
                unset($ws_worker->games[$i]);
            }
            else{
                foreach($ws_worker->games[$i]['players'] as $playerKey => $playerValue){
                    $connection=$ws_worker->connections[$playerKey];
                    $connection->send(\json_encode(['leaving'=>$self->id]));
                }
                unset($ws_worker->games[$i]['players'][$connection->id]);
            }
            var_dump($ws_worker->games[$i]);
        }
    }
    echo "Connection closed\n";
};

Worker::runAll();