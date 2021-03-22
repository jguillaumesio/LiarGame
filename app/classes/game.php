<?php
namespace classes;

class Game{
    private static array $gameList=[];
    private string $id;
    private string $name;
    private int $state;
    private int $creator;
    private string $word;
    private int $max;
    private string $liar;
    private array $players;
    private bool $public;

    function __construct(string $name, int $max, Player $player, string $id, bool $public)
    {
        $this->id = $id;
        $this->state = 0;
        $this->max = $max;
        $this->name=$name;
        $this->creator = $player->getId();
        $this->players[]=$player;
        $this->public=$public;
        Game::$gameList[]=$id;
    }

    function getState():int{
        return $this->state;
    }

    function getMax():int{
        return $this->max;
    }

    function getCreator():int{
        return $this->creator;
    }

    function getPlayer(int $id){
    	foreach($this->getPlayers() as $player){
    		if($player->getId()==$id){
    			return($player);
    		}
    	}
    }
    
    function getPlayers():array{
        return $this->players;
    }

    function getLiar():string{
        return $this->liar;
    }

    function getPlayersNumber():int{
        return count($this->players);
    }

    function getName():string{
        return $this->name;
    }

    function isPublic():bool{
        return($this->public);
    }

    function setMenteur(){
        $this->word="Menteur";
        return(\json_encode(\array_merge(['starting'=>true],$this->playersToJson())));
    }

    function setPlayers(array $players){
    	$this->players=$players;
    }
    
    static function gameExist(string $id):bool{
        if(\in_array($id,Game::$gameList)){
            return True;
        }
        else{
            return False;
        }
    }

    static function deleteGame(string $id){
        foreach(Game::$gameList as $key => $value){
            if($value==$id){
                unset(Game::$gameList[$key]);
            }
        }
    }

    static function gameList():array{
        return Game::$gameList;
    }

    function creating():string{
    	return(\json_encode(\array_merge(['created'=>true],$this->playersToJson())));
    }

    function joining(Player $player):string
    {
        $this->players[$player->getId()]=$player;
        return(\json_encode(\array_merge(['joined'=>true],$this->playersToJson())));
    }

    function starting():string{
        global $list;
        $this->word = $list[\array_rand($list)];
        $this->liar=\array_rand($this->players);
        $this->state=1;
        return(\json_encode(\array_merge(['starting'=>true],$this->playersToJson())));
    }

    function leaving(string $id):string{
        unset($this->players[$id]);
        return(\json_encode(['leaving'=>$id]));
    }

    function stopping():string{
        Game::deleteGame($this->id);
        return(\json_encode(\array_merge(['stopped'=>true],$this->playersToJson())));
    }
    
    function playersToJson():array{
    	$convertedPlayers=[];
    	var_dump($this->getPlayers());
    	$self=clone $this;
    	foreach($self->getPlayers() as $player){
    		$convertedPlayers[$player->getId()]=$player->getPseudo();
    	}
    	$self->setPlayers($convertedPlayers);
    	return(\get_object_vars($self));
    }
}