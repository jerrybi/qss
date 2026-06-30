/*自定义js事件 by:moTzxx*/

$(document).ready(function () {
    /**
     * 左侧导航栏 显示与隐藏的设置
     */
    $(".layui-header .menu-switch").click(function () {
        var leftView = $(".layui-bg-black");
        var hidden = leftView.is(':hidden');
        var layui_body = $(".layui-layout-admin .layui-body");
        var layui_footer = $(".layui-layout-admin .layui-footer");
        leftView.animate({width: 'toggle'});
        if(hidden){
            //如果当前 左侧导航栏是隐藏状态
            slide_leftView(layui_body,1);
            slide_leftView(layui_footer,1);
        }else {
            //如果当前 左侧导航栏是显示状态
            slide_leftView(layui_body,0);
            slide_leftView(layui_footer,0);
        }
    });

    $(".layui-tab-title .layui-this").click(function () {
        var tag = $(this).hasClass('title-selected');
        if(!tag){
            $(".layui-tab-title .layui-this").removeClass('title-selected');
            $(this).addClass('title-selected');
            var url = $(this).attr('url');
            $(".layui-body .iframe-body").attr('src',url);
        }
    });
    /**
     * 导航菜单栏 触发事件
     */
    $(".layui-side-scroll .a-to-Url").click(function () {
        var action = $(this).attr('action');
        var nav_menu_id = $(this).attr('nav_menu_id');
        //TODO 此处进行判断当前用户是否有权限进入
        var checkUrl = $("#check_login").attr('url');
        var loginUrl = $("#check_login").attr('login');
        var tag_token = $("#check_login").attr('tag_token');
        $.post(
            checkUrl,
            {'_token':tag_token,'nav_menu_id':nav_menu_id},
            function (result) {
                if(result.status == 200){
                    $(".layui-body .iframe-body").attr('src',action);
                }else{
                    //失败
                    window.location.href = loginUrl;
                }
            },"JSON");
    });
    /**
     * 锁屏点击事件
     */
    $("#LockScreen").on("click",function(){
        window.sessionStorage.setItem("lockCMS",true);
        lockPage();
    });

    // 解锁
    $("body").on("click","#unlock",function(){
        if($(this).siblings(".admin-header-lock-input").val() == ''){
            layer.msg("Please input unlock password!");
            $(this).siblings(".admin-header-lock-input").focus();
        }else{
            if($(this).siblings(".admin-header-lock-input").val() == "123456"){
                window.sessionStorage.setItem("lockCMS",false);
                $(this).siblings(".admin-header-lock-input").val('');
                layer.closeAll("page");
            }else{
                layer.msg("Password is wrong,please input again!");
                $(this).siblings(".admin-header-lock-input").val('').focus();
            }
        }
    });
    $(document).on('keydown', function() {
        if(event.keyCode == 13) {
            $("#unlock").click();
        }
    });

    // 全屏切换
    $("#FullScreen").click(function () {
        var fullscreenElement =
            document.fullscreenElement ||
            document.mozFullScreenElement ||
            document.webkitFullscreenElement;

        if (fullscreenElement == null) {
            entryFullScreen();
            $("#FullScreen span").html('Exit fullscreen');
            $(".layui-nav-item .img-FullScreen").attr('src','../cms/images/icon/fullscreen_exit.png')
        } else {
            exitFullScreen();
            $("#FullScreen span").html('Enter fullscreen');
            $(".layui-nav-item .img-FullScreen").attr('src','../cms/images/icon/fullscreen.png')
        }
    });

    $(".mul-to-Url").click(function () {
        var all_nav = $('.layui-nav-child').parent();
        all_nav.removeClass('layui-nav-itemed');
        $(this).parent().parent().parent().addClass('layui-nav-itemed');
    });
    $(".single-to-Url").click(function () {
        var all_nav = $('.layui-nav-item');
        all_nav.removeClass('layui-nav-itemed');
    });


    $(".form-opAdmins .input-pwd-re").blur(function () {
        var pwd = $(".form-opAdmins .input-pwd").val();
        var pwd_re = $(".form-opAdmins .input-pwd-re").val();
        var tip = '';
        if ( pwd!='' && (pwd == pwd_re)){
            $(".span-dot").addClass('layui-bg-orange');
            tip = 'The two passwords are the same!';
        }else {
            $(".span-dot").removeClass('layui-bg-green');
            tip = 'The two passwords are inconsistent!'
        }
        $(".form-opAdmins .tip-pwd").html(tip);
    });


});
window.onload = function(){
    //要初始化的东西 TODO 我就奇怪为啥有的代码在$(document).function()中就不行！！！
    // 判断是否显示锁屏
    if(window.sessionStorage.getItem("lockCMS") == "true"){
        lockPage();
    }
};
/*------------------------------------------------------------------------------------------------------*/
//锁屏
function lockPage(){
    layer.open({
        title : false,
        type : 1,
        content : '	<div class="admin-header-lock" id="lock-box">'+
        '<div class="admin-header-lock-img"><img src="../cms/images/user.png"/></div>'+
        '<div class="admin-header-lock-name" id="lockUserName">I am just a passenger</div>'+
        '<div class="input_btn">'+
        '<input type="password" class="admin-header-lock-input layui-input" autocomplete="off" placeholder="Please input password to unlock." name="lockPwd" id="lockPwd" />'+
        '<button class="layui-btn" id="unlock">Unlock</button>'+
        '</div>'+
        '<p>Please input “123456”，or not unlock successfully！！！</p>'+
        '</div>',
        closeBtn : 0,
        shade : 0.9
    })
    $(".admin-header-lock-input").focus();
}
/**
 * 控制左侧导航栏 显示/隐藏
 * @param viewTag 对应标签
 * @param tag 1：显示  0：隐藏
 */
function slide_leftView(viewTag,tag) {
    if (tag){
        // viewTag.animate({left:parseInt(viewTag.css('left'),200) == 200 ? + viewTag.outerWidth() : 200});
        viewTag.animate({left:parseInt(viewTag.css('left'),230) == 230 ? + viewTag.outerWidth() : 230});
    }else {
        viewTag.animate({left:parseInt(viewTag.css('left'),10) == 0 ? - viewTag.outerWidth() : 0});
    }
}


// 进入全屏：
function entryFullScreen() {
    var docE = document.documentElement;
    if (docE.requestFullScreen) {
        docE.requestFullScreen();
    } else if (docE.mozRequestFullScreen) {
        docE.mozRequestFullScreen();
    } else if (docE.webkitRequestFullScreen) {
        docE.webkitRequestFullScreen();
    }
}

// 退出全屏
function exitFullScreen() {
    var docE = document;
    if (docE.exitFullscreen) {
        docE.exitFullscreen();
    } else if (docE.mozCancelFullScreen) {
        docE.mozCancelFullScreen();
    } else if (docE.webkitCancelFullScreen) {
        docE.webkitCancelFullScreen();
    }
}
/*----------------------------------------------------------------------------------------------------*/
/**
 * 导航菜单处理函数 包括 "添加"、"修改"
 * @param op_url URL 地址
 * @param tag 操作标识：add / edit
 * @param title
 * @constructor
 */
function ToOpenPopups(op_url,title,width,height,manualRefresh) {
    var widthTag = width?width:'70%';
    var heightTag = height?height:'65%';
    var openPopus = layer.open({
        type: 2,
        shade:0.61,
        shadeClose:true,
        anim:4,
        moveOut: true,
        title: title,
        maxmin: true, //开启最大化最小化按钮
        area: [widthTag, heightTag],
        content: op_url, //可以出现滚动条
        //content: [op_url, 'no'], //如果你不想让iframe出现滚动条
        end:function(){
            if(!manualRefresh){
                window.location.reload();
            }else if(typeof manualRefresh === 'function'){
                manualRefresh();
            }
        }
    });
    layer.style(openPopus, {
        background: '#fff',
        
    });
}


