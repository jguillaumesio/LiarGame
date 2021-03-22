<?php

require_once './vendor/autoload.php';
require_once '../app/classes/game.php';
require_once '../database.php';

use classes\Game;
use classes\Player;
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
    	$player=new Player($connection->id,$dataArray->pseudo,$dataArray->game_id);
        $ws_worker->players[$connection->id]=$player;
        $ws_worker->games[$dataArray->game_id]=new Game($dataArray->name,$dataArray->max,$player,$dataArray->game_id,$dataArray->public);
        $connection->send($ws_worker->games[$dataArray->game_id]->creating());
    }

    if(isset($dataArray->join)) {
        $game=$ws_worker->games[$dataArray->game_id];
        if ($game->getMax() > $game->getPlayersNumber()) {
            if ($game->getState() == 0) {
            	$player=new Player($connection->id,$dataArray->pseudo,$dataArray->game_id);
                $ws_worker->players[$connection->id][] = $player;
                $data = $game->joining($player);
                $connection->send($data);
                foreach ($game->getPlayers() as $player) {
                    if ($player->getId() != $self->id) {
                    	$connection = $ws_worker->connections[$player->getId()];
                    	$connection->send(\json_encode(['id' => $self->id, 'joining' => $game->getPlayer($self->id)->getPseudo()]));
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
            foreach($game->getPlayers() as $player){
            	$connection = $ws_worker->connections[$player->getId()];
            	if($game->getLiar()==$player->getId()){
                    $copy=clone $game;
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
        if(Game::gameExist($dataArray->game_id)){
            $game=$ws_worker->games[$dataArray->game_id];
            if($game->getCreator()==$connection->id){
                $data=$game->stopping();
                foreach($game->getPlayers() as $player){
                    $connection = $ws_worker->connections[$player->getId()];
                    $connection->send($data);
                    unset($ws_worker->players[$player->getId()]);
                }
                unset($ws_worker->games[$dataArray->game_id]);
            }
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
    	$player=$ws_worker->players[$connection->id];
    	$game=$ws_worker->games[$player->getGame()];
    	if(isset($player)){
    		if($connection->id==$game->getCreator()){
    			foreach($game->getPlayers() as $p){
    				$connection = $ws_worker->connections[$p->getId()];
    				$connection->send($game->stopping());
    			}
    		}else{
    			$data=$game->leaving($self->id);
    			foreach($game->getPlayers() as $p){
    				$connection=$ws_worker->connections[$p->getId()];
    				$connection->send($data);
    			}
    		}
    		unset($ws_worker->games[$player->getGame()]);
    	}
    }
    echo "Connection closed\n";
};

Worker::runAll();
