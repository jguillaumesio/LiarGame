<?php
namespace controllers;

use services\UI\IndexUI;
use Ubiquity\attributes\items\router\Route;
use Ubiquity\utils\http\URequest;

/**
 * Controller IndexController
 * @property \Ajax\php\ubiquity\JsUtils $jquery
 */
class IndexController extends ControllerBase {
    
    private $uiService;
    
    public function initialize(){
        parent::initialize();
        $this->uiService = new IndexUI( $this->jquery );
    }
    
	public function index() {
	    $this->uiService->landPage();
		$this->jquery->renderView('IndexController/index.html');
	}
	
	#[Route('/create', name:'create', methods:['get'])]
	public function create(){
	    $this->uiService->create();
	    $this->jquery->renderView('IndexController/create.html');
	}
	
	#[Route('/game/{id}', name:'game', methods:['get','post'])]
	public function game(string $id){
	    $this->uiService->game($id);
	    if(URequest::isPost()){
	        if(URequest::post('name')!= null && URequest::post('pseudo')!= null && URequest::post('max')!= null){
	            $this->jquery->execAtLast('window.ws.send(\'{"create":true,"game_id":'.\json_encode($id).',"max":"'.URequest::post('max').'","pseudo":"'.URequest::post('pseudo').'"}\');$("#startGame").removeClass("hidden");');
	        }
	    }
	    else{
	        $this->jquery->execAtLast('
            window.ws.send(\'{"gameExist":'.\json_encode($id).'}\');
            $("#pseudo_dialog").removeClass("hidden");
            $("#pseudo_dialog").modal({closable:false});
            $("#pseudo_dialog").modal("show");');
	    }
	    $this->jquery->renderView('IndexController/game.html');
	}
}
