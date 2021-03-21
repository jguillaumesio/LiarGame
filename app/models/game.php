<?php
namespace models;

class Game{
    private static array $gameList=[];
    private string $id;
    private int $state;
    private int $creator;
    private string $word;
    private int $max;
    private string $liar;
    private array $players;

    function __construct(int $creator_id, int $max, string $pseudo, string $id)
    {
        $this->id = $id;
        $this->state = 0;
        $this->max = $max;
        $this->creator = $creator_id;
        $this->players[$creator_id]=$pseudo;
        Game::$gameList[]=$id;
    }

    function getState():int{
        return $this->state;
    }

    function getCreator():int{
        return $this->creator;
    }

    function getPlayers():array{
        return $this->players;
    }

    function getLiar():string{
        return $this->liar;
    }

    function setMenteur(){
        $this->word="Menteur";
        return(\json_encode(\array_merge(['starting'=>true],\get_object_vars($this))));
    }

    static function gameExist(string $id){
        if(\in_array($id,Game::$gameList)){
            return(\json_encode(["gameExist"=>true]));
        }
        else{
            return(\json_encode(["gameExist"=>false]));
        }
    }

    static function deleteGame(string $id){
        foreach(Game::$gameList as $key => $value){
            if($value==$id){
                unset(Game::$gameList[$key]);
            }
        }
    }

    function creating(){
        return(\json_encode(\array_merge(['created'=>true],\get_object_vars($this))));
    }

    function joining(string $id, string $pseudo)
    {
        $this->players[$id]=$pseudo;
        return(\json_encode(\array_merge(['joined'=>true],\get_object_vars($this))));
    }

    function starting(){
        global $list;
        $this->word = $list[\array_rand($list)];
        $this->liar=\array_rand($this->players);
        $this->state=1;
        return(\json_encode(\array_merge(['starting'=>true],\get_object_vars($this))));
    }

    function leaving(string $id){
        unset($this->players[$id]);
        return(\json_encode(['leaving'=>$id]));
    }

    function stopping(){
        return(\json_encode(\array_merge(['stopped'=>true],\get_object_vars($this))));
    }
}