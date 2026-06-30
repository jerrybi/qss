layui.use(['laypage', 'layer'], function () {
    var lay_page = layui.laypage;
    var tbodyTag = $("#paginator");
    var page_limit = tbodyTag.attr('page_limit');
    var record_num = tbodyTag.attr('record_num');
    var page_url = tbodyTag.attr('page_url');
    var ajax_page_fun = eval(tbodyTag.attr('ajax_page_fun'));
    lay_page.render({
        elem: 'paginator'
        , limit: page_limit
        , count: record_num
        , layout: ['count', 'prev', 'page', 'next', 'refresh', 'skip']
        , jump: function (obj, first) {
            //obj包含了当前分页的所有参数 首次不执行
            if (!first) {
                var curPage = obj.curr;
                ajax_page_fun(page_url, {curr_page:curPage});
            }
        }
        , theme: '#283560'
    });
});

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
    upload.render({
        elem: '.btn_upload_file'
        , accept:'file' //上传文件类型
        , url: image_upload_url
        , size:2048  //KB
        , before: function (obj) {
            //预读本地文件示例，不支持ie8
        }
        , done: function (res) {
            dialog.tip(res.message);
            console.log(res.data.url);
            console.log(this);
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
            //演示失败状态，并实现重传
            return layer.msg('Upload failed,please upload again');
        }
    });
});

function OpenPopups(domId,title,width,height) {
    var widthTag = width?width:'70%';
    var heightTag = height?height:'65%';
    var openPopus = layer.open({
        type: 1,
        shade:0.61,
        shadeClose:true,
        anim:4,
        moveOut: true,
        title: title,
        maxmin: true, //开启最大化最小化按钮
        area: [widthTag, heightTag],
        content: $('#'+domId), //可以出现滚动条
        //content: [op_url, 'no'], //如果你不想让iframe出现滚动条
        end:function(){

        }
    });
    layer.style(openPopus, {
        background: '#fff',
    });
    return openPopus;
}

function ToOpenPopups(op_url,title,width,height) {
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
        }
    });
    layer.style(openPopus, {
        background: '#fff',

    });
}

function delRecord(id,callback) {
    var remove_class = '.tr-normal-' + id;
    layer.msg('Confirm to delete this record?', {
        time: 0 //不自动关闭
        ,btn: ['Confirm', 'Leave']
        ,shade:0.61,
        shadeClose:true,
        anim:4,
        moveOut: true
        ,yes: function(index){
            if(callback){
                callback(id);
            }
            $(remove_class).remove();
            layer.close(index);
        }
    });
    return false;
}