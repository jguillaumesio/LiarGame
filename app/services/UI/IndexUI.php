<?php

namespace services\UI;

use Ajax\php\ubiquity\JsUtils;
use Ubiquity\controllers\Router;
use Ubiquity\translation\Translator;
use Ubiquity\translation\TranslatorManager;

class IndexUI {
    protected $jquery;
    protected $semantic;
    
    public function __construct(JsUtils $jq) {
        $this->jquery = $jq;
        $this->semantic = $jq->semantic ();
    }
    
    public function landPage() {
        $this->jquery->semantic()->htmlButton('create',TranslatorManager::trans('create',[],'test'),null);
        $this->jquery->getOnClick('#create',Router::path('create'),'#response',[
            'hasLoader'=>false,
        ]);
        $this->jquery->semantic()->htmlButton('join',TranslatorManager::trans('join',[],'test'));
        $form=$this->jquery->semantic()->htmlForm('form_join');
        $form->addInput('join_id');
        $form->addButton('submit_join',TranslatorManager::trans('join',[],'test'));
        $form->addButton('cancel_join','Cancel',null,'$("#join_dialog").addClass("hidden");$("#join_dialog").modal("hide")');
        $this->jquery->postFormOnClick('#submit_join',Router::path('game'),'form_join','body',[
            'before'=>'url+="/"+$("#join_id").val();',
            'hasLoader'=>false,
            'jsCallback'=>'window.history.pushState("Liar Game", "Liar Game", url);'
            
        ]);
        $this->jquery->getOnClick('#join',Router::path('join'),'#response');
    }
    
    public function create(){
        $form=$this->jquery->semantic()->htmlForm('form_create');
        $form->addInput('name',TranslatorManager::trans('name',[],'test'))->addRule("empty");
        $form->addInput('pseudo',TranslatorManager::trans('pseudo',[],'test'))->addRule("empty");
        $form->addInput('max',TranslatorManager::trans('maxPlayers',[],'test'))->addRules(["empty","integer"]);
        $form->addCheckbox('public',TranslatorManager::trans('public',[],'test'));
        $form->addButton('cancel',TranslatorManager::trans('cancel',[],'test'));
        $id=\uniqid();
        $form->addSubmit('submit_create',TranslatorManager::trans('create',[],'test'),null,Router::path('game',['id'=>$id]),'body',[
            'hasLoader'=>false,
            'jsCallback'=>'window.history.pushState("Liar Game", "Liar Game", "/game/'.$id.'");'
        ]);
        $this->jquery->getOnClick('#cancel',Router::path('_default'),'body',[
            'hasLoader'=>false,
        ]);
    }
    
    public function game(string $id){
        $form=$this->jquery->semantic()->htmlForm('form_pseudo');
        $form->addInput('pseudo')->addRule("empty");
        $form->addButton('submit_pseudo',TranslatorManager::trans('validate',[],'test'),null,'window.ws.send(\'{"game_id":"'.$id.'","pseudo":"\'+$("#pseudo").val()+\'","join":true}\');$("#pseudo_dialog").modal("hide");$("#pseudo_dialog").remove();');
        $this->jquery->semantic()->htmlButton('startGame',TranslatorManager::trans('start',[],'test'),'hidden','$("#stopGame").removeClass("hidden");$("#startGame").addClass("hidden");window.ws.send(\'{"game_id":"'.$id.'","start":true}\');');
        $this->jquery->semantic()->htmlButton('stopGame',TranslatorManager::trans('finish',[],'test'),'hidden','window.ws.send(\'{"game_id":"'.$id.'","stop":true}\');');
    }

    public function join(){
        $this->jquery->semantic()->htmlButton('cancel','Retour');
        $this->jquery->getOnClick('#cancel',Router::path('_default'),'body',[
            'hasLoader'=>false,
        ]);
        $table=$this->jquery->semantic()->htmlTable("table_join",1,3);
        $table->setHeaderValues([TranslatorManager::trans('labelname',[],'test'),TranslatorManager::trans('labelplayer',[],'test'),""]);
        $table->setRowValues(0,["","",""]);
    }
}