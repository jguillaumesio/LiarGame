<?php

require_once './vendor/autoload.php';
require_once '../app/classes/game.php';
require_once '../database.php';

use classes\Game;
use Workerman\Worker;

$ws_worker = new Worker("websocket://0.0.0.0:2346");

$ws_worker->count = 4;

$ws_worker->games=[];

$ws_worker->players=[];

$ws_worker->onMessage = function ($connection, $data) use ($ws_worker){
    global $ws_worker;
    $dataArray=\json_decode($data);
    $self=$connection;

    if(isset($dataArray->joinList)){
        $to_send=[];
        foreach(Game::gameList() as $key => $val){
            $game=$ws_worker->games[$val];
            if($game->isPublic() && $game->getState()==0 && $game->getPlayersNumber() < $game->getMax()){
                $to_send[$val]=['name'=>$game->getName(),'players'=>$game->getPlayersNumber()."/".$game->getMax()];
            }
        }
        $connection->send(\json_encode(["list"=>$to_send]));
    }

    if(isset($dataArray->create) && $dataArray->create){
        $ws_worker->players[$connection->id][]=$dataArray->game_id;
        $ws_worker->games[$dataArray->game_id]=new models\Game($connection->id,$dataArray->name,$dataArray->max,$dataArray->pseudo,$dataArray->game_id,$dataArray->public);
        $connection->send($ws_worker->games[$dataArray->game_id]->creating());
    }

    if(isset($dataArray->join)) {
        $game=$ws_worker->games[$dataArray->game_id];
        if ($game->getMax() > $game->getPlayersNumber()) {
            if ($game->getState() == 0) {
                $ws_worker->players[$connection->id][] = $dataArray->game_id;
                $data = $game->joining($connection->id, $dataArray->pseudo);
                $connection->send($data);
                foreach ($game->getPlayers() as $i => $value) {
                    if ($i != $self->id) {
                        $connection = $ws_worker->connections[$i];
                        $connection->send(\json_encode(['id' => $self->id, 'joining' => $game->getPlayers()[$self->id]]));
                    }
                }
            } else {
                $connection->send(\json_encode(['canJoin' => false, 'alreadyStarted' => true, 'message' => 'Cette partie a déjà commencée']));
            }
        } else {
            $connection->send(\json_encode(['canJoin' => false, 'full' => true, 'message' => 'Cette partie est pleine']));
        }
    }

    if(isset($dataArray->start)){
        $game=$ws_worker->games[$dataArray->game_id];
        if($game->getCreator()==$connection->id){
            $data=$game->starting();
            foreach($game->getPlayers() as $i => $value){
                $connection = $ws_worker->connections[$i];
                if($game->getLiar()==$i){
                    $copy=$game;
                    $connection->send($copy->setMenteur());
                    unset($copy);
                }
                else{
                    $connection->send($data);
                }
            }
        }
    }

    if(isset($dataArray->stop)){
        $game=$ws_worker->games[$dataArray->game_id];
        if($game->getCreator()==$connection->id){
            $data=$game->stopping();
            foreach($game->getPlayers() as $i => $value){
                $connection = $ws_worker->connections[$i];
                $connection->send($data);
                foreach($ws_worker->players[$i] as $gkey=>$gvalue){
                    if($gvalue==$dataArray->game_id){
                        unset($ws_worker->players[$i][$gkey]);
                    }
                }
            }
            unset($ws_worker->games[$dataArray->game_id]);
        }
    }

    if(isset($dataArray->gameExist)){
        if(Game::gameExist($dataArray->gameExist)){
            $game=$ws_worker->games[$dataArray->gameExist];
            if ($game->getMax() > $game->getPlayersNumber()){
                if ($game->getState() == 0) {
                    $connection->send(\json_encode(['canJoin' => true]));
                }else{
                    $connection->send(\json_encode(['canJoin' => false, 'message' => 'Cette partie a déjà commencée']));
                }
            }else{
                $connection->send(\json_encode(['canJoin' => false, 'message' => 'Cette partie est pleine']));
            }
        }else{
            $connection->send(\json_encode(['canJoin' => false, 'message' => 'Cette partie n\'existe pas']));
        }
    }
};

$ws_worker->onClose = function ($connection) use($ws_worker){
    echo $connection->id;
    global $ws_worker;
    $self=$connection;
    if(isset($ws_worker->players[$connection->id])){
        foreach($ws_worker->players[$connection->id] as $i=>$val){
            if($connection->id==$ws_worker->games[$val]->getCreator()){
                foreach($ws_worker->games[$val]->getPlayers() as $key => $value){
                    $connection = $ws_worker->connections[$key];
                    $connection->send($ws_worker->games[$val]->stopping());

                }
                unset($ws_worker->games[$val]);
            }
            else{
                $data=$ws_worker->games[$val]->leaving($self->id);
                foreach($ws_worker->games[$val]->getPlayers() as $playerKey => $playerValue){
                    $connection=$ws_worker->connections[$playerKey];
                    $connection->send($data);
                }
            }
        }
    }
    echo "Connection closed\n";
};

Worker::runAll();