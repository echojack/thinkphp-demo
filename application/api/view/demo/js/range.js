$(function(){
    $('.jsInfoli').click(function (){

        if($(this).parent('li').hasClass('active') == false)
        {
            $('.jsInfoli').parent('li').siblings('li').removeClass('active');
            $(this).parent('li').addClass('active');
            return;
        }

        $('.jsInfoli').parent('li').siblings('li').removeClass('active');
    })
})