/**
 * ajax 获取并加载每页的数据
 * @param toUrl
 * @param postData
 * @constructor
 */
function ToAjaxOpForPageEvents(toUrl,postData) {
    $.post(
        toUrl,
        postData,
        function (result) {
            if(result.status == 200){
                var str_html = '';
                $.each(result.data,function (i,e) {
                    str_html +=
                        "<tr class=\"tr-normal-"+e.id+"\">\n" +
                        "                <td>"+e.id+"</td>\n" +
                        "                <td class=\"title\">"+e.name+"</td>\n" +
                        "                <td class=\"title\">"+e.venue+"</td>\n" +
                        "                <td class=\"title\">"+e.country+"</td>\n" +
                        "                <td class=\"title\">"+e.start_time+"</td>\n" +
                        "                <td class=\"title\">"+e.end_time+"</td>\n" +
                        "                <td>"+e.list_order +"</td>\n" +
                        // "                <td>"+e.is_encrypt +"</td>\n" +
                        // "                <td>"+e.event_key +"</td>\n" +
                        "                <td>\n" +
                        "                    <div class=\"layui-btn-group\">\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Edit event' \n" +
                        "                                onclick=\"editForOpenPopups('✎ Edit event','"+e.id+"','720px','95%')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe642;</i>\n" +
                        "                        </button>\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Remove event' \n" +
                        "                                onclick=\"delPostRecord('"+e.id+"')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe640;</i>\n" +
                        "                        </button>\n" +
//                        "<button class=\"layui-btn layui-btn-sm\" title=\"Op log\"\n" +
//                        "                        onclick=\"viewLogOpenPopups('☁ Op log','"+e.id+"')\">\n" +
//                        "                    <i class=\"layui-icon\">&#xe60e;</i>\n" +
//                        "                </button>"+
                          "<button class=\"layui-btn layui-btn-sm\" title=\"Visitor Rules\"\n" +
                          "                        onclick=\"ruleForOpenPopups('✎ Edit rule','{$vo.id}', '720px', '460px')\">\n" +
                          "                    <i class=\"layui-icon\">&#xe716;</i>\n" +
                          "                </button>\n" +
                        "                    </div>\n" +
                        "                </td>\n" +
                        "            </tr>";
                });
                $(".table-tbody-normal").html(str_html);
                layui.form.render();//细节！这个好像要渲染一下！
            }else{
                //失败
                layer.msg(result.message);
            }
        },"JSON");
}