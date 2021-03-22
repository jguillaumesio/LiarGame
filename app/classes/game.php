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

    function __construct(int $creator_id, string $name, int $max, string $pseudo, string $id, bool $public)
    {
        $this->id = $id;
        $this->state = 0;
        $this->max = $max;
        $this->name=$name;
        $this->creator = $creator_id;
        $this->players[$creator_id]=$pseudo;
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
        return(\json_encode(\array_merge(['starting'=>true],\get_object_vars($this))));
    }

    static function gameExist(string $id){
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

    static function gameList(){
        return Game::$gameList;
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
        Game::deleteGame($this->id);
        return(\json_encode(\array_merge(['stopped'=>true],\get_object_vars($this))));
    }
}