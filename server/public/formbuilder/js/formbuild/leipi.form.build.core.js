(function() {
    var LPB = window.LPB = window.LPB || {
        plugins: [],
        genSource: function() {
            $("#source").val($("#build fieldset").html().replace(/\n\ \ \ \ \ \ \ \ \ \ \ \ /g, "\n"));
            var $temptxt = $("<div>").html($("#build").html());
            //scrubbbbbbb
            $($temptxt).find(".component").attr({
                "title": null,
                "data-original-title": null,
                "data-type": null,
                "data-content": null,
                "rel": null,
                "trigger": null,
                "style": null
            });
            $($temptxt).find(".valtype").attr("data-valtype", null).removeClass("valtype");
            $($temptxt).find(".component").removeClass("component");
            $($temptxt).find("form").attr({
                "id": null,
                "style": null
            });
            var html = $($temptxt).find("fieldset").html();
            $("#dest").val(html.replace(/\n\ \ \ \ \ \ \ \ \ \ \ \ /g, "\n"));
            this.genFields();
        },
        genFields:function(){
            var items = [];
            var inputs = $("#build fieldset").find("input,textarea,select");
            var that = this;
            $.each(inputs,function(i,data){
                var name = $(data).attr("name");
                var title = $(data).parent().parent().find(".leipiplugins-orgname").text().trim().replace(/^\*?/,"");
                if(name){
                    var type = name.split("_")[0];
                    if(type == 'Tel'){
                        title = title + " "+$(data).attr("title");
                    }
                    if(type == 'Radiobox' || type == 'Checkbox'){
                        var groupName = $(data).parent().attr("data-group");
                        if(groupName && groupName.length > 0){
                            title = groupName;
                        }
                    }
                }
                if(name && name.length >0 && title && title.length > 0){
                    if(that.findItem(items,name) < 0){
                        items.push({name:name,title:title});
                    }
                }
            });
            $("#fields").val(JSON.stringify(items));
        },
        findItem:function(items,name){
            for(var i=0;i<items.length;i++){
                if(items[i].name == name){
                    return i;
                }
            }
            return -1;
        }

    };
    /* 表单名称控件 form_name
  acc  是 class="component" 的DIV
  e 是 class="leipiplugins" 的控件
  */
    LPB.plugins['form_name'] = function(active_component, leipiplugins) {
        var plugins = 'form_name',
            popover = $(".popover");
        //右弹form  初始化值
        $(popover).find("#orgvalue").val($(leipiplugins).val());
        //右弹form  取消控件
        $(popover).delegate(".btn-danger", "click",
            function(e) {
                e.preventDefault();
                active_component.popover("hide");
            });
        //右弹form  确定控件
        $(popover).delegate(".btn-info", "click",
            function(e) {
                e.preventDefault(); //阻止元素发生默认的行为(例如,当点击提交按钮时阻止对表单的提交
                var inputs = $(popover).find("input");
                $.each(inputs,
                    function(i, e) {
                        var attr_name = $(e).attr("id"); //属性名称
                        var attr_val = $("#" + attr_name).val();
                        if (attr_name == 'orgvalue') {
                            $(leipiplugins).attr("value", attr_val);
                            active_component.find(".leipiplugins-orgvalue").text(attr_val);
                        }
                        active_component.popover("hide");
                        LPB.genSource(); //重置源代码
                    });
            });

    }
})();
$(document).ready(function() {
    // $("#navtab").delegate("#sourcetab", "click",
    //     function(e) {
    //         LPB.genSource();
    //     });
    $(".form-block").delegate(".component", "mousedown",
        function(md) {
            $(".popover").remove();

            md.preventDefault();
            var tops = [];
            var mouseX = md.pageX;
            var mouseY = md.pageY;
            var $temp;
            var timeout;
            var $this = $(this);
            var delays = {
                main: 0,
                form: 120
            };
            var type;

            if ($this.parent().parent().attr("id") === "components") {
                type = "main";
            } else {
                type = "form";
            }
            console.log("[form build][mousedown]","type:"+type);
            var delayed = setTimeout(function() {
                    if (type === "main") {
                        $temp = $("<form class='form-horizontal span6' id='temp'></form>").append($this.clone());
                    } else {
                        if ($this.attr("id") !== "legend") {
                            $temp = $("<form class='form-horizontal span6' id='temp'></form>").append($this);
                        }
                    }

                    $("body").append($temp);

                    $temp.css({
                        "position": "absolute",
                        "top": mouseY - ($temp.height() / 2) + "px",
                        "left": mouseX - ($temp.width() / 2) + "px",
                        "opacity": "0.9"
                    }).show();

                    var half_box_height = ($temp.height() / 2);
                    var half_box_width = ($temp.width() / 2);
                    var $target = $("#target");
                    // var tar_pos = $target.position();
                    var tar_pos = $target.offset();
                    var $target_component = $("#target .component");

                    //record the tops value when mousedown
                    if (tar_pos && mouseX > tar_pos.left && mouseX < tar_pos.left + $target.width() + $temp.width() / 2 && mouseY > tar_pos.top && mouseY < tar_pos.top + $target.height() + $temp.height() / 2) {
                        tops = $.grep($target_component,
                            function(e) {
                                return ($(e).offset().top - mouseY + half_box_height > 0 && $(e).attr("id") !== "legend");
                            });
                        console.log("[form build][mousedown]",tops);
                    }

                    $(document).delegate("body", "mousemove",
                        function(mm) {

                            var mm_mouseX = mm.pageX;
                            var mm_mouseY = mm.pageY;

                            $temp.css({
                                "top": mm_mouseY - half_box_height + "px",
                                "left": mm_mouseX - half_box_width + "px"
                            });
                            console.log('left:' + tar_pos.left + ' mouseX:' + mm_mouseX + ' targetWidth:' + $target.width() + ' tempWidth:' + $temp.width() + ' top:' + tar_pos.top + ' mouseY:' + mm_mouseY + ' targetHeight:' + $target.height() + ' tempHeight:' + $temp.height());
                            if (tar_pos && mm_mouseX > tar_pos.left && mm_mouseX < tar_pos.left + $target.width() + $temp.width() / 2 && mm_mouseY > tar_pos.top && mm_mouseY < tar_pos.top + $target.height() + $temp.height() / 2) {
                                $("#target").css("background-color", "#fafdff");
                                $target_component.css({
                                    "border-top": "1px solid white",
                                    "border-bottom": "none"
                                });
                                tops = $.grep($target_component,
                                    function(e) {
                                        // return ($(e).position().top - mm_mouseY + half_box_height > 0 && $(e).attr("id") !== "legend");
                                        return ($(e).offset().top - mm_mouseY + half_box_height > 0 && $(e).attr("id") !== "legend");
                                    });
                                if (tops.length > 0) {
                                    $(tops[0]).css("border-top", "1px solid #22aaff");
                                } else {
                                    if ($target_component.length > 0) {
                                        $($target_component[$target_component.length - 1]).css("border-bottom", "1px solid #22aaff");
                                    }
                                }
                            } else {
                                $("#target").css("background-color", "#fff");
                                $target_component.css({
                                    "border-top": "1px solid white",
                                    "border-bottom": "none"
                                });
                                $target.css("background-color", "#fff");
                            }
                        });

                    $("body").delegate("#temp", "mouseup",
                        function(mu) {
                            mu.preventDefault();

                            var mu_mouseX = mu.pageX;
                            var mu_mouseY = mu.pageY;
                            // var tar_pos = $target.position();
                            var tar_pos = $target.offset();

                            $("#target .component").css({
                                "border-top": "1px solid white",
                                "border-bottom": "none"
                            });
                            console.log("[form build][mouseup]",tar_pos);
                            console.log("[form build][mouseup]","mu_mouseX:"+mu_mouseX+" mu_mouseY:"+mu_mouseY+" half_box_width:"+half_box_width+" half_box_height:"+half_box_height+" tar_pos_left:"+tar_pos.left+" tar_pos_top:"+tar_pos.top+" target_width:"+$target.width()+" target_height:"+$target.height());
                            // acting only if mouse is in right place
                            if (tar_pos && mu_mouseX + half_box_width > tar_pos.left && mu_mouseX - half_box_width < tar_pos.left + $target.width() && mu_mouseY + half_box_height > tar_pos.top && mu_mouseY - half_box_height < tar_pos.top + $target.height()) {
                                console.log("[form build][mouseup]","--1--");
                                $temp.attr("style", null);
                                //replace control name
                                var elem = $temp.find(".component");
                                var title = elem.attr("title");
                                if(type == 'main'){
                                    if(title && title.length > 0){
                                        var controlName = title+"_"+new Date().getTime();
                                        var uploadIndex = new Date().getTime()+"";
                                        // where to add
                                        if(title == 'Tel'){
                                            if (tops.length > 0) {
                                                $($temp.html()
                                                    .replace(/leipiNewField1/g,title+"_1"+new Date().getTime())
                                                    .replace(/leipiNewField2/g,title+"_2"+new Date().getTime())
                                                    .replace(/leipiNewField3/g,title+"_3"+new Date().getTime())
                                                    .replace(/\{leipiUploadIndex\}/g,uploadIndex))
                                                    .insertBefore(tops[0]);
                                            } else {
                                                $("#target fieldset").append($temp.append("\n\n\ \ \ \ ").html()
                                                    .replace(/leipiNewField1/g,title+"_1"+new Date().getTime())
                                                    .replace(/leipiNewField2/g,title+"_2"+new Date().getTime())
                                                    .replace(/leipiNewField3/g,title+"_3"+new Date().getTime())
                                                    .replace(/\{leipiUploadIndex\}/g,uploadIndex));
                                            }
                                        }else{
                                            if (tops.length > 0) {
                                                $($temp.html()
                                                    .replace(/leipiNewField/g,controlName)
                                                    .replace(/\{leipiUploadIndex\}/g,uploadIndex))
                                                    .insertBefore(tops[0]);
                                            } else {
                                                $("#target fieldset").append($temp.append("\n\n\ \ \ \ ").html()
                                                    .replace(/leipiNewField/g,controlName)
                                                    .replace(/\{leipiUploadIndex\}/g,uploadIndex));
                                            }
                                        }
                                    }
                                }else{
                                    console.log("[form build][mouseup]",tops);
                                    if (tops.length > 0) {
                                        $($temp.html()).insertBefore(tops[0]);
                                        //fetch this insertd object
                                        var prev = $(tops[0]).prev();
                                        console.log("[form build][mouseup][prev]",prev);
                                        if(prev){
                                            $(prev).popover({trigger:"manual"});
                                            $(prev).trigger("click");
                                        }
                                    } else {
                                        $("#target fieldset").append($temp.append("\n\n\ \ \ \ ").html());
                                    }
                                }
                            } else {
                                console.log("[form build][mouseup]","--2--");
                                // no add
                                $("#target .component").css({
                                    "border-top": "1px solid white",
                                    "border-bottom": "none"
                                });
                                tops = [];
                            }

                            //clean up & add popover
                            $target.css("background-color", "#fff");
                            $(document).undelegate("body", "mousemove");
                            $("body").undelegate("#temp", "mouseup");
                            $("#target .component").popover({
                                trigger: "manual"
                            });
                            $temp.remove();
                            LPB.genSource();
                        });
                },
                delays[type]);

            $(document).mouseup(function() {
                clearInterval(delayed);
                return false;
            });
            $(this).mouseout(function() {
                clearInterval(delayed);
                return false;
            });
        });

    //activate legend popover
    $("#target .component").popover({
        trigger: "manual"
    });
    //popover on click event
    $("#target").delegate(".component", "click",
        function(e) {
            console.log("[form build][click]");
            e.preventDefault();
            //$(".popover").hide();
            var active_component = $(this);
            active_component.popover("show");
            //class="leipiplugins"
            var leipiplugins = active_component.find(".leipiplugins"),
                plugins = $(leipiplugins).attr("leipiplugins"); //leipiplugins="text"
            //exec plugins
            if (typeof(LPB.plugins[plugins]) == 'function') {
                try {
                    LPB.plugins[plugins](active_component, leipiplugins);
                } catch(e) {
                    alert('control exception!');
                }
            } else {
                alert("control error!"+typeof(LPB.plugins[plugins]));
            }

        });
});