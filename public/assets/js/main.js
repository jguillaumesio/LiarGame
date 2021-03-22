function redirectWithMessage(url,message){
	$.get(url, function( data ) {
		$( 'body' ).html( data );
		$('body').toast({position:'top center',class: 'error',showIcon: false,message: message});
	});
	window.history.pushState('Liar Game', 'Liar Game', url);
}

base_url = "http://127.0.0.1:8090/";
window.ws = new WebSocket("ws:/127.0.0.1:2346");
window.ws.onmessage = function(e) {
	var obj = JSON.parse(e.data);
	console.log(e.data);
	if('connected' in obj){
		localStorage.setItem('id',obj.connected);
	}
	
	if('created' in obj){
		localStorage.setItem('id',obj.connected);
		$("#players").append('<div class="item"><div class="content"><p class="header">'+obj.players[obj.creator]+'</p></div></div>');
	}
	
	if('joined' in obj){
		var players=JSON.parse(JSON.stringify(obj.players));
		console.log(players);
		for(const[i,value] of Object.entries(players)){
			$("#players").append('<div class="item" id="'+i+'"><div class="content"><p class="header">'+value+'</p></div></div>');
		}
	}
	
	if('joining' in obj){
		if(obj.joining){
			$("#players").append('<div class="item" id="'+obj.id+'"><div class="content"><p class="header">'+obj.joining+'</p></div></div>');
		}
		else{
			redirectWithMessage(base_url,obj.message);
		}
	}
	
	if('leaving' in obj){
		console.log(obj.leaving);
		$("#"+obj.leaving).remove();
	}
	
	if('starting' in obj){
		$("#link").remove();
		$("#game").append("<p>"+obj.word+"</p>");
		var seconds = 0;
		var min = 0;
		function incrementSeconds() {
			if(seconds==59){
				min+=1;
				seconds=0;
			}
    		else{
				seconds += 1;
			}
    		if(seconds<10 && min<10){
				$("#sec").text("0"+seconds);
				$("#min").text("0"+min);
			}
			else if(seconds>=10 && min<10){
				$("#sec").text(seconds);
				$("#min").text("0"+min);
			}
			else if(min>=10 && seconds<10){
				$("#sec").text("0"+seconds);
				$("#min").text(min);
			}
			else{
				$("#sec").text(seconds);
				$("#min").text(min);
			}
		}
		var cancel = setInterval(incrementSeconds, 1000);
	}
	
	if('canJoin' in obj){
		console.log(obj);
		if(!obj.canJoin){
			redirectWithMessage(base_url,obj.message);
		}
		else{
			var dialog=$("#pseudo_dialog");
			dialog.removeClass("hidden");
			dialog.modal({closable:false});
			dialog.modal("show");
		}
	}
	
	if('stopped' in obj){
		if(obj.stopped){
			$.get(base_url, function( data ) {
				$( "body" ).html( data );
				$('body').toast({position:'top center',class: 'success',showIcon: false,message: 'Cette partie est finie'});
				});
			window.history.pushState("Liar Game", "Liar Game", base_url);
		}
	}

	if('list' in obj){
		var list=JSON.parse(JSON.stringify(obj.list));
		$("#table_join tbody tr:first").remove();
		if(list.length==0){
			$("#table_join").find('tbody').append('<tr><td colSpan="3">Il n\'y a pas de partie disponible, créez en une !</td></tr>');
		}
		for(const[i,value] of Object.entries(list)){
			console.log(value.players.toString());
			$("#table_join").find('tbody').append('<tr id="htmltablecontent--0" class=""><th id="htmltr-htmltablecontent--0-0" class="">'+value.name+'</th> <th id="htmltr-htmltablecontent--0-1" class="">'+value.players+'</th> <th id="htmltr-htmltablecontent--0-2" class=""><a href="/game/'+i+'">Join</a></th></tr>');
		}
	}
};