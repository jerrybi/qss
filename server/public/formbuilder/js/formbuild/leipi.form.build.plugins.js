/*e.preventDefault();//阻止元素发生默认的行为(例如,当点击提交按钮时阻止对表单的提交*/

/* 文本框控件 text
acc  是 class="component" 的DIV 
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['text'] = function(active_component, leipiplugins) {
    var plugins = 'text',
        popover = $(".popover");
    //右弹form  初始化值
    $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgvalue").val($(leipiplugins).val());
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            var type = $(popover).find("input[type='radio'][name='orgtype']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        //要break
                        case 'orgvalue':
                            //$(leipiplugins).val(attr_val);
                            $(leipiplugins).attr("value", attr_val);
                            if(require == '1'){
                                if(type != ''){
                                    $(leipiplugins).attr("lay-verify","required|"+type);
                                }else{
                                    $(leipiplugins).attr("lay-verify","required");
                                }
                            }else{
                                $(leipiplugins).removeAttr("lay-verify");
                            }
                            break;
                        //没有break
                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                            }
                            break;
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
                layui.form.render();
        });
        layui.form.on("radio(orgrequire)",function(data){
            var value = data.value;
            if(value == '1'){
                $(popover).find("input[type='radio'][name='orgtype']").attr('disabled',false);
            }else{
                $(popover).find("input[type='radio'][name='orgtype']").attr('disabled',true);
            }
            layui.form.render();
        });
        var vfy = $(leipiplugins).attr("lay-verify");
        $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
        if(vfy && vfy.length > 0){
            var items = vfy.split("|");
            $.each(items,function(i,data){
                if(data == 'required'){
                    $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
                    $(popover).find("input[type='radio'][name='orgtype']").attr('disabled',false);
                }else{
                    $("input[type='radio'][name='orgtype'][value='"+data+"']").attr("checked",true);
                }
            });
        }
        layui.form.render();
}
/* 多行文本框控件 textarea
acc  是 class="component" 的DIV
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['textarea'] = function(active_component, leipiplugins) {
    var plugins = 'textarea',
        popover = $(".popover");
    //右弹form  初始化值
    $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgvalue").val($(leipiplugins).val());
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        //要break
                        case 'orgvalue':
                            //$(leipiplugins).val(attr_val);
                            $(leipiplugins).text(attr_val);
                            if(require == '1'){
                                $(leipiplugins).attr("lay-verify","required");
                            }else{
                                $(leipiplugins).removeAttr("lay-verify");
                            }
                            break;
                        //没有break
                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                            }
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
            layui.form.render();
        });
    var vfy = $(leipiplugins).attr("lay-verify");
    $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
    if(vfy && vfy.length > 0){
        var items = vfy.split("|");
        $.each(items,function(i,data){
            if(data == 'required'){
                $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
            }
        });
    }
    layui.form.render();
}
/* 下拉框控件 select
acc  是 class="component" 的DIV
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['select'] = function(active_component, leipiplugins) {
    var plugins = 'select',
        popover = $(".popover");
    //右弹form  初始化值
    $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    var val = $.map($(leipiplugins).find("option"),
        function(e, i) {
            return $(e).text()
        });
    val = val.join("\r");
    $(popover).find("#orgvalue").text(val);
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {
            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        //要break
                        case 'orgvalue':
                            var options = attr_val.split("\n");
                            $(leipiplugins).html("");
                            $.each(options,
                                function(i, e) {
                                    $(leipiplugins).append("\n      ");
                                    $(leipiplugins).append($("<option>").text(e));
                                });
                            //$(leipiplugins).text(attr_val);
                            if(require == '1'){
                                $(leipiplugins).attr("lay-verify","required");
                            }else{
                                $(leipiplugins).removeAttr("lay-verify");
                            }
                            break;

                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                            }
                            break;
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
                layui.form.render();
        });
        var vfy = $(leipiplugins).attr("lay-verify");
        $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
        if(vfy && vfy.length > 0){
            var items = vfy.split("|");
            $.each(items,function(i,data){
                if(data == 'required'){
                    $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
                }
            });
        }
        layui.form.render();
}

/* 复选控件 checkbox
acc  是 class="component" 的DIV
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['checkbox'] = function(active_component, leipiplugins) {
    var plugins = 'checkbox',
        popover = $(".popover");
    //右弹form  初始化值
    // $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgname").val(active_component.find(".leipiplugins-orgname").text().trim().replace(/^\*?/,""));
    var dataGroup = active_component.find(".leipiplugins-orgvalue").attr("data-group");
    if(dataGroup && dataGroup != ''){
        $(popover).find("#orggroupname").val(dataGroup.trim());
    }
    val = $.map($(leipiplugins),
        function(e, i) {
            return $(e).val().trim()
        });
    val = val.join("\r");
    $(popover).find("#orgvalue").text(val);
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        //要break
                        case 'orgvalue':
                            var checkboxes = attr_val.split("\n");
                            var html = "<!-- Multiple Checkboxes -->\n";
                            $.each(checkboxes,
                                function(i, e) {
                                    if (e.length > 0) {
                                        var vName = $(leipiplugins).eq(0).attr("name");
                                        var vTitle = $(leipiplugins).eq(0).attr("title");
                                        var orginline = $(leipiplugins).eq(0).attr("orginline");
                                        if (!vName) vName = '';
                                        if (!vTitle) vTitle = '';
                                        if (!orginline) orginline = '';
                                        if(require == '1'){
                                            html += '<input type="checkbox" name="' + vName + '" title="' + e + '" value="' + e + '" orginline="' + orginline + '" class="leipiplugins" leipiplugins="checkbox" lay-skin="primary" lay-verify="checkboxrequired">';
                                        }else{
                                            html += '<input type="checkbox" name="' + vName + '" title="' + e + '" value="' + e + '" orginline="' + orginline + '" class="leipiplugins" leipiplugins="checkbox" lay-skin="primary">';
                                        }
                                        if(orginline == 'vertical'){
                                            html += '<br/>';
                                        }
                                    }
                                    $(active_component).find(".leipiplugins-orgvalue").html(html);
                                });
                            break;

                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                            }
                            break;
                        case 'orggroupname':
                            var groupName = attr_val;
                            if(groupName && groupName != ''){
                                active_component.find(".leipiplugins-orgvalue").attr("data-group",groupName);
                                //check if there is the same group name in the form
                                var groups = $("div[data-group='"+groupName+"']");
                                if(groups && groups.length > 0){
                                    var curName = active_component.find(".leipiplugins-orgname").html();
                                    var checkboxs = null;
                                    for(var i=0;i<groups.length;i++){
                                        var name = $(groups[i]).parent().find(".leipiplugins-orgname").html();
                                        if(name != curName){
                                            checkboxs = $(groups[i]).find("input[type='checkbox']");
                                            break;
                                        }
                                    }
                                    if(checkboxs && checkboxs.length > 0){
                                        var controlName = $(checkboxs[0]).attr("name");
                                        var currentCheckboxs = active_component.find("input[type='checkbox']");
                                        $.each(currentCheckboxs,function(i,data){
                                            $(data).attr("name",controlName);
                                        });
                                    }
                                }
                            }
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
                layui.form.render();
        });
        var vfy = $(leipiplugins).attr("lay-verify");
        $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
        if(vfy && vfy.length > 0){
            var items = vfy.split("|");
            $.each(items,function(i,data){
                if(data == 'checkboxrequired'){
                    $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
                }
            });
        }
        layui.form.render();
}

/* 复选控件 radio
acc  是 class="component" 的DIV
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['radio'] = function(active_component, leipiplugins) {
    var plugins = 'radio',
        popover = $(".popover");
    //右弹form  初始化值
    // $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgname").val(active_component.find(".leipiplugins-orgname").text().trim().replace(/^\*?/,""));
    var dataGroup = active_component.find(".leipiplugins-orgvalue").attr("data-group");
    if(dataGroup && dataGroup != ''){
        $(popover).find("#orggroupname").val(dataGroup.trim());
    }
    val = $.map($(leipiplugins),
        function(e, i) {
            return $(e).val().trim()
        });
    val = val.join("\r");
    $(popover).find("#orgvalue").text(val);
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        //要break
                        case 'orgvalue':
                            var checkboxes = attr_val.split("\n");
                            var html = "<!-- Multiple Checkboxes -->\n";
                            $.each(checkboxes,
                                function(i, e) {
                                    if (e.length > 0) {
                                        var vName = $(leipiplugins).eq(0).attr("name"),
                                            vTitle = $(leipiplugins).eq(0).attr("title"),
                                            orginline = $(leipiplugins).eq(0).attr("orginline");
                                        if (!vName) vName = '';
                                        if (!vTitle) vTitle = '';
                                        if (!orginline) orginline = '';
                                        if(require == '1'){
                                            html += '<input type="radio" name="' + vName + '" title="' + e + '" value="' + e + '" orginline="' + orginline + '" class="leipiplugins" leipiplugins="radio" lay-skin="primary" lay-verify="radiorequired">';
                                        }else{
                                            html += '<input type="radio" name="' + vName + '" title="' + e + '" value="' + e + '" orginline="' + orginline + '" class="leipiplugins" leipiplugins="radio" lay-skin="primary">';
                                        }
                                        if(orginline == 'vertical'){
                                            html += '<br/>';
                                        }
                                    }
                                    $(active_component).find(".leipiplugins-orgvalue").html(html);
                                });
                            break;

                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                            }
                            break;
                        case 'orggroupname':
                            var groupName = attr_val;
                            if(groupName && groupName != ''){
                                active_component.find(".leipiplugins-orgvalue").attr("data-group",groupName);
                                //check if there is the same group name in the form
                                var groups = $("div[data-group='"+groupName+"']");
                                if(groups && groups.length > 0){
                                    var curName = active_component.find(".leipiplugins-orgname").html();
                                    var radioboxs = null;
                                    for(var i=0;i<groups.length;i++){
                                        var name = $(groups[i]).parent().find(".leipiplugins-orgname").html();
                                        if(name != curName){
                                            radioboxs = $(groups[i]).find("input[type='radio']");
                                            break;
                                        }
                                    }
                                    if(radioboxs && radioboxs.length > 0){
                                        var controlName = $(radioboxs[0]).attr("name");
                                        var currentRadioboxs = active_component.find("input[type='radio']");
                                        $.each(currentRadioboxs,function(i,data){
                                            $(data).attr("name",controlName);
                                        });
                                    }
                                }
                            }
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
                layui.form.render();
        });
        var vfy = $(leipiplugins).attr("lay-verify");
        $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
        if(vfy && vfy.length > 0){
            var items = vfy.split("|");
            $.each(items,function(i,data){
                if(data == 'radiorequired'){
                    $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
                }
            });
        }
        layui.form.render();
}

/* 上传控件 uploadfile
acc  是 class="component" 的DIV
e 是 class="leipiplugins" 的控件
*/
LPB.plugins['uploadfile'] = function(active_component, leipiplugins) {
    var plugins = 'uploadfile',
        popover = $(".popover");
    //右弹form  初始化值
    // $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgname").val(active_component.find(".leipiplugins-orgname").text().trim().replace(/^\*?/,""));
    $(popover).find("#orgsize").val($(leipiplugins).parent().find(".btn_upload_file").attr("data-size"));
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                                $(leipiplugins).attr("lay-verify","filerequired");
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                                $(leipiplugins).removeAttr("lay-verify");
                            }
                            break;
                        case 'orgsize':
                            $(leipiplugins).parent().find(".btn_upload_file").attr("data-size",attr_val);
                            break;
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
            layui.form.render();
        });
    var vfy = $(leipiplugins).attr("lay-verify");
    $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
    if(vfy && vfy.length > 0){
        var items = vfy.split("|");
        $.each(items,function(i,data){
            if(data == 'filerequired'){
                $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
            }
        });
    }
    layui.form.render();
}

LPB.plugins['uploadimage'] = function(active_component, leipiplugins) {
    var plugins = 'uploadimage',
        popover = $(".popover");
    //右弹form  初始化值
    // $(popover).find("#orgname").val($(leipiplugins).attr("title"));
    $(popover).find("#orgname").val(active_component.find(".leipiplugins-orgname").text().trim().replace(/^\*?/,""));
    $(popover).find("#orgsize").val($(leipiplugins).parent().find(".btn_upload_img").attr("data-size"));
    $(popover).find("#orgwidth").val($(leipiplugins).parent().find("img").attr("width"));
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        case 'orgname':
                            $(leipiplugins).attr("title", attr_val);
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                                $(leipiplugins).attr("lay-verify","imagerequired");
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                                $(leipiplugins).removeAttr("lay-verify");
                            }
                            break;
                        case 'orgsize':
                            $(leipiplugins).parent().find(".btn_upload_img").attr("data-size",attr_val);
                            break;
                        case 'orgwidth':
                            $(leipiplugins).parent().find("img").attr("width",attr_val);
                            break;
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
            layui.form.render();
        });
    var vfy = $(leipiplugins).attr("lay-verify");
    $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
    if(vfy && vfy.length > 0){
        var items = vfy.split("|");
        $.each(items,function(i,data){
            if(data == 'imagerequired'){
                $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
            }
        });
    }
    layui.form.render();
}

LPB.plugins['html'] = function(active_component, leipiplugins) {
    var plugins = 'html',
        popover = $(".popover");
    var editor = UE.getEditor('formeditor-ue',{
        initialFrameHeight:100,
        zIndex:9000
    });
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {
            UE.delEditor('formeditor-ue');
            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {
            var content = editor.getContent();
            $(leipiplugins).html(content);
            UE.delEditor('formeditor-ue');
            active_component.popover("hide");
            LPB.genSource(); //重置源代码
        });
    setTimeout(function () {
        var html = $(leipiplugins).html();
        editor.setContent(html);
    },1000);
};

LPB.plugins['tel'] = function(active_component, leipiplugins) {
    var plugins = 'tel',
        popover = $(".popover");
    //右弹form  初始化值
    $(popover).find("#orgname").val(active_component.find(".leipiplugins-orgname").text().trim().replace(/^\*?/,""));
    //右弹form  取消控件
    $(popover).delegate(".btn-danger", "click",
        function(e) {

            active_component.popover("hide");
        });
    //右弹form  确定控件
    $(popover).delegate(".btn-info", "click",
        function(e) {

            var inputs = $(popover).find("input");
            if ($(popover).find("textarea").length > 0) {
                inputs.push($(popover).find("textarea")[0]);
            }
            var require = $(popover).find("input[type='radio'][name='orgrequire']:checked").val();
            $.each(inputs,
                function(i, e) {
                    var attr_name = $(e).attr("id"); //属性名称
                    var attr_val = $(e).val();
                    switch (attr_name) {
                        case 'orgname':
                            if(require == '1'){
                                active_component.find(".leipiplugins-orgname").html("<span class='red'>*</span>"+attr_val);
                                $(leipiplugins).attr("lay-verify","required|phone");
                            }else{
                                active_component.find(".leipiplugins-orgname").text(attr_val);
                                $(leipiplugins).attr("lay-verify","phone");
                            }
                            break;
                        default:
                            $(leipiplugins).attr(attr_name, attr_val);
                    }
                    active_component.popover("hide");
                    LPB.genSource(); //重置源代码
                });
            layui.form.render();
        });
    var vfy = $(leipiplugins).attr("lay-verify");
    $("input[type='radio'][name='orgrequire'][value='0']").attr("checked",true);
    if(vfy && vfy.length > 0){
        var items = vfy.split("|");
        $.each(items,function(i,data){
            if(data == 'required'){
                $("input[type='radio'][name='orgrequire'][value='1']").attr("checked",true);
            }
        });
    }
    layui.form.render();
};