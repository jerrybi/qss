/**
 * 公用 table 分页模块
 */
layui.use(['laypage', 'layer'], function () {
    var lay_page = layui.laypage;
    var tbodyTag = $(".table-tbody-normal");
    var page_limit = tbodyTag.attr('page_limit');
    var record_num = tbodyTag.attr('record_num');
    var page_url = tbodyTag.attr('page_url');
    var ajax_page_fun = eval(tbodyTag.attr('ajax_page_fun'));
    lay_page.render({
        elem: 'demo2-1'
        , limit: page_limit
        , count: record_num
        , layout: ['count', 'prev', 'page', 'next', 'refresh', 'skip']
        , jump: function (obj, first) {
            //obj包含了当前分页的所有参数 首次不执行
            if (!first) {
                $(".curr_page").val(obj.curr);
                var postData = $(".form-search").serialize();
                ajax_page_fun(page_url, postData);
            }
        }
        // , theme: '#283560'
    });
});

/**
 * 图片资源上传
 */
layui.use('upload', function () {
    var upload = layui.upload;
    //普通图片上传
    upload.render({
        elem: '.btn_upload_img'
        , type: 'images'
        , exts: 'jpg|png|gif|jpeg' //设置一些后缀，用于演示前端验证和后端的验证
        , accept:'images' //上传文件类型
        , url: image_upload_url
        , before: function (obj) {
            //预读本地文件示例，不支持ie8
            // var index = this.item[0].dataset.index;
            // obj.preview(function (index, file, result) {
            //     $('.upload_img_preview'+index).attr('src', result); //图片链接（base64）
            // });
        }
        , done: function (res) {
            var index = this.item[0].dataset.index;
            dialog.tip(res.message);
            //如果上传成功
            if (res.status == 200) {
                $('.upload_img_url'+index).val(res.data.url2);
                $('.upload_img_preview'+index).attr('src',res.data.url2);
            }
        }
        , error: function () {
            //演示失败状态，并实现重传
            return layer.msg('Upload failed,please upload again');
        }
    });
    //文件上传
    var dataSize = $(".btn_upload_file").attr("data-size");
    var size = 0;
    if(dataSize && dataSize.length>0){
        size = parseFloat(dataSize);
    }
    var layerIndex;
    upload.render({
        elem: '.btn_upload_file'
        , accept:'file' //上传文件类型
        , url: image_upload_url
        ,size:size
        , before: function (obj) {
            //预读本地文件示例，不支持ie8
        }
        , done: function (res) {
            dialog.tip(res.message);
            console.log(res.data.url);
            console.log(this);
            layer.close(layerIndex);
            var index = this.item[0].dataset.index;
            var callback = eval(this.item[0].getAttribute('upload_file_cb'+index));
            //如果上传成功
            if (res.status == 200) {
                $('.upload_file_url'+index).val(res.data.url2);
                $('.upload_file_preview'+index).text(res.data.url2);
                if(callback){
                    callback(res.data.url);
                }
            }
        }
        , error: function () {
            layer.close(layerIndex);
            //演示失败状态，并实现重传
            return layer.msg('Upload failed,please upload again');
        }
        ,progress:function(n,elem,res,index){
            var index = layer.load('Uploading '+n+'%', {
                shade: [0.3,'#fff'] //0.1透明度的白色背景
            });
            layer.close(layerIndex);
            layerIndex = index;
        }
    });
});

/**
 * 通用化 添加/更新数据操作
 * @param obj
 */
function opFormPostRecord(obj,failcb) {
    var toUrl = $(obj).attr('op_url');
    var postData = $(".form-op-normal").serialize();
    ToPostPopupsDeal(toUrl, postData,failcb);
}

function opFormPost(obj,failcb) {
    var toUrl = $(obj).attr('op_url');
    var postData = $(".form-op-normal").serialize();
    ToPostDeal(toUrl, postData,failcb);
}

/**
 * 打开添加 操作窗口
 * @param obj
 */
function addForOpenPopups(obj,title,width,height,manualRefresh) {
    var op_url = $(obj).attr('op_url');
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

/**
 * 打开编辑 操作窗口
 * @param title
 * @param id
 * @param width
 * @param height
 */
function editForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".op_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function ruleForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".rule_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function bindAdminForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".bind_admin_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function bindAccountForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".bind_account_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function manageForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".manage_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function viewForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".view_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function industryForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".industry_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function productForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".product_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

function updatePasswordForOpenPopups(title,id,width,height,manualRefresh) {
    var op_url = $(".update_password_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, width, height,manualRefresh);
}

/**
 * 打开日志 查看窗口
 * @param title
 * @param id
 */
function viewLogOpenPopups(title,id,manualRefresh) {
    var op_url = $(".log_url").val();
    op_url = op_url.replace('opid', id);
    ToOpenPopups(op_url, title, '95%', '95%',manualRefresh);
}
/**
 * 删除记录操作
 * @param id
 */
function delPostRecord(id) {
    var toUrl = $(".op_url").val();
    toUrl = toUrl.replace('opid', id);
    ToDelItem(id, toUrl, '.tr-normal-' + id);
}
function delPostItem(id,obj) {
    var toUrl = $(".op_url").val();
    toUrl = toUrl.replace('opid', id);
    delItem(id, toUrl, obj);
}
function delSinglePostRecord(obj,id) {
    var toUrl = $(obj).attr('op_url');
    toUrl = toUrl.replace('opid', id);
    ToDelItem(id, toUrl, '.tr-normal-' + id);
}
// 除去页面所显示的记录 传递 div
function ToRemoveDiv(tag) {
    $(tag).remove();
}

/**
 * 对导航菜单的 ajax请求处理
 * @param toUrl
 * @param postData
 * @constructor
 */
function ToPostPopupsDeal(toUrl,postData,failcb) {
    $.post(
        toUrl,
        postData,
        function (result) {
            dialog.tip(result.message);
            if(result.status == 200){
                setTimeout(function(){
                    var index = parent.layer.getFrameIndex(window.name); //先得到当前 iframe层的索引
                    parent.layer.close(index); //再执行关闭
                },2000);
            }else{
                //失败
                //layer.msg(result.message);
                if(failcb){
                    failcb();
                }
            }
        },"JSON");
}

function ToPostDeal(toUrl,postData,failcb) {
    $.post(
        toUrl,
        postData,
        function (result) {
            dialog.tip(result.message);
            if(result.status == 200){
                window.location.reload();
            }else{
                //失败
                //layer.msg(result.message);
                if(failcb){
                    failcb();
                }
            }
        },"JSON");
}

/**
 * 删除记录
 * @param id 记录ID
 * @param toUrl 请求 URL
 * @constructor
 */
function ToDelItem(id,toUrl,remove_class) {
    var tag_token = $(".tag_token").val();
    var postData = {'id':id,'tag':'del','_token':tag_token};
    layer.msg('Confirm to delete this record?', {
        time: 0 //不自动关闭
        ,btn: ['Confirm', 'Leave']
        ,shade:0.61,
        shadeClose:true,
        anim:4,
        moveOut: true
        ,yes: function(index){
            afterDelItem(toUrl,postData,remove_class);
        }
    });
}

function delItem(id,toUrl,obj) {
    var tag_token = $(".tag_token").val();
    var postData = {'id':id,'tag':'del','_token':tag_token};
    layer.msg('Confirm to delete this record?', {
        time: 0 //不自动关闭
        ,btn: ['Confirm', 'Leave']
        ,shade:0.61,
        shadeClose:true,
        anim:4,
        moveOut: true
        ,yes: function(index){
            delItemCallback(toUrl,postData,obj);
        }
    });
}

/**
 * 删除元素 请求
 * @param toUrl
 * @param postData
 * @param remove_class
 */
function afterDelItem(toUrl,postData,remove_class) {
    $.post(
        toUrl,
        postData,
        function (result) {
            dialog.tip(result.message);
            if(result.status == 200){
                ToRemoveDiv(remove_class);
                window.location.reload();
            }else{
                //失败
                layer.msg(result.message);
            }
        },"JSON");
}

function delItemCallback(toUrl,postData,obj) {
    $.post(
        toUrl,
        postData,
        function (result) {
            dialog.tip(result.message);
            if(result.status == 200){
                if(obj){
                    obj.del();
                }
            }else{
                //失败
                layer.msg(result.message);
            }
        },"JSON");
}

function refreshContent(){
    var formSearch = $('.form-search');
    if(formSearch){
        formSearch.submit();
    }else{
        window.location.reload(true);
    }
}

function goBack() {
    window.history.back()
}