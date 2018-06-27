$(function (){
	//评论页轮播图
	function plPlay(){
		var $span = $('.hImgTop .hnum');
		var $swipe = $('#hUlImgbox');
		var $li = $swipe.find('li');
		var len = $li.length;
		
		if(len > 1){
			$span.html('1/'+len);

			new Swipe($swipe.get(0),{
				continuous: true,
				callback: function(index, element) {
					$span.html((index+1)+'/'+len);
				}
			})
		}else{
			$span.html('');
		}
		
	}

    function init(){
		plPlay();
	}
	init();
	


});