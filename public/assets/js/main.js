function getBaseUrl() {
    var re = new RegExp(/^.*\/\/[^\/]+/);
    return re.exec(window.location.href);
}
url="http://127.0.0.1:8090/";
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
			$('body').toast({class: 'warning',showIcon: false,message: 'Cette partie n\'existe pas'});
		}
	}
	
	if('leaving' in obj){
		console.log(obj.leaving);
		$("#"+obj.leaving).remove();
	}
	
	if('starting' in obj){
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
				$("#timer").text("0"+min+":0"+seconds);
			}
			else if(seconds){
				$("#timer").text("0"+min+":"+seconds);
			}
			else if(min>10){
				$("#timer").text(min+":0"+seconds);
			}
			else{
				$("#timer").text(min+":"+seconds);
			}
		}
		var cancel = setInterval(incrementSeconds, 1000);
	}
	
	if('gameExist' in obj){
		console.log(obj);
		if(!obj.gameExist){
			$.get(url, function( data ) {
				$( "body" ).html( data );
				$('body').toast({class: 'warning',showIcon: false,message: 'Cette partie n\'existe pas'});
				});
			window.history.pushState("Liar Game", "Liar Game", url);
			
		}
	}
	
	if('stopped' in obj){
		if(obj.stopped){
			$.get(url, function( data ) {
				$( "body" ).html( data );
				$('body').toast({class: 'warning',showIcon: false,message: 'Cette partie n\'existe pas'});
				});
			window.history.pushState("Liar Game", "Liar Game", url);
		}
	}
};