<?php

namespace services\UI;

use Ajax\php\ubiquity\JsUtils;
use Ubiquity\controllers\Router;

class IndexUI {
    protected $jquery;
    protected $semantic;
    
    public function __construct(JsUtils $jq) {
        $this->jquery = $jq;
        $this->semantic = $jq->semantic ();
    }
    
    public function landPage() {
        $this->jquery->semantic()->htmlButton('create','Create a game',null);
        $this->jquery->getOnClick('#create',Router::path('create'),'#response',[
            'hasLoader'=>false,
        ]);
        $this->jquery->semantic()->htmlButton('join','Join a game',null,'$("#join_dialog").removeClass("hidden");$("#join_dialog").modal({closable:false});$("#join_dialog").modal("show")');
        $form=$this->jquery->semantic()->htmlForm('form_join');
        $form->addInput('join_id');
        $form->addButton('submit_join','Rejoindre');
        $form->addButton('cancel_join','Cancel',null,'$("#join_dialog").addClass("hidden");$("#join_dialog").modal("hide")');
        $this->jquery->postFormOnClick('#submit_join',Router::path('game'),'form_join','body',[
            'before'=>'url+="/"+$("#join_id").val();',
            'hasLoader'=>false,
            'jsCallback'=>'window.history.pushState("Liar Game", "Liar Game", url);'
            
        ]);
    }
    
    public function create(){
        $form=$this->jquery->semantic()->htmlForm('form_create');
        $form->addInput('name');
        $form->addInput('pseudo');
        $form->addInput('max');
        $form->addButton('submit_create','Créer');
        $form->addButton('cancel','Annuler');
        $id=\uniqid();
        $this->jquery->postFormOnClick('#submit_create',Router::path('game',['id'=>$id]),'form_create','body',[
            'hasLoader'=>false,
            'jsCallback'=>'window.history.pushState("Liar Game", "Liar Game", "/game/'.$id.'");'
        ]);
        $this->jquery->getOnClick('#cancel',Router::path('_default'),'#response',[
            'hasLoader'=>false,
        ]);
    }
    
    public function game(string $id){
        $form=$this->jquery->semantic()->htmlForm('form_pseudo');
        $form->addInput('pseudo');
        $form->addButton('submit_pseudo','Valider',null,'window.ws.send(\'{"game_id":"'.$id.'","pseudo":"\'+$("#pseudo").val()+\'","join":true}\');$("#pseudo_dialog").modal("hide");$("#pseudo_dialog").remove();');
        $this->jquery->semantic()->htmlButton('startGame','Commencer la partie','hidden','$("#stopGame").removeClass("hidden");$("#startGame").addClass("hidden");window.ws.send(\'{"game_id":"'.$id.'","start":true}\');');
        $this->jquery->semantic()->htmlButton('stopGame','Stopper la partie','hidden','window.ws.send(\'{"game_id":"'.$id.'","stop":true}\');');
    }
}