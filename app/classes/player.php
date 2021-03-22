<?php
namespace classes;

class Player{
	private string $id;
	private string $pseudo;
	private string $game;
	
	function __construct(string $id, string $pseudo, string $game)
	{
		$this->id = $id;
		$this->pseudo = $pseudo;
		$this->game = $game;
	}
	
	function getId():string{
		return $this->id;
	}
	
	function getPseudo():string{
		return $this->pseudo;
	}
	
	function getGame():string{
		return $this->game;
	}
}