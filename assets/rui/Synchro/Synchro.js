/*
SAMPLE:
	var th =new Synchro.thread();
	th.start({ test : 123 });
	th.push(function(th,o){
		setTimeout(function(){
			console.log(1+", test="+o.test);
			th.sync({ test : 234 });
		},1000);
	});
	th.push(function(th, o){
		setTimeout(function(){
			console.log(2+", test="+o.test);
			th.sync();
		},1000);
	});
*/

//-------------------------------------
// 遅延処理の逐次実行の同期制御機能
window.Synchro ={
	
	//-------------------------------------
	// コンストラクタ
	thread : function() {
		
		this.stack =[];
		this.isRunning =false;
		this.lastArgs =undefined;
		
		//-------------------------------------
		// はじめの処理に同期
		this.start =function(args) {
			
			this.isRunning =true;
			this.lastArgs =args;
			
			if (this.stack.length > 0) {
				
				this.stack[0](this, this.lastArgs);
			}
		};
		
		//-------------------------------------
		// 処理の追加
		this.push =function(f) {
			
			this.stack.push(f);
			
			if (this.isRunning && this.stack.length == 1) {
				
				this.sync(this.lastArgs);
			}
		};
		
		//-------------------------------------
		// 次の処理に同期
		this.sync =function(args) {
			
			this.lastArgs =args;
			
			if (this.stack.length > 0) {
				
				this.stack.shift();
			}
			
			if (this.stack.length > 0) {
				
				this.stack[0](this, this.lastArgs);
			}
		};
	}
};

