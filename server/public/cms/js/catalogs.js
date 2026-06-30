/**
 * ajax 获取并加载每页的数据
 * @param toUrl
 * @param postData
 * @constructor
 */
function ToAjaxOpForPages(toUrl,postData) {
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
                        "                <td class=\"title\">"+e.type+"</td>\n" +
                        "                <td class=\"title\">"+e.category+"</td>\n" +
                        "                <td class=\"title\">"+e.sub_category+"</td>\n" +
                        "                <td class=\"title\">"+e.description+"</td>\n" +
                        "                <td class=\"title\">"+e.advanced_rate+"</td>\n" +
                        "                <td class=\"title\">"+e.standard_rate+"</td>\n" +
                        "                <td class=\"title\">"+(e.have_onsite_rate?e.onsite_rate:'-')+"</td>\n" +
                        "                <td class=\"title\"><img src='"+e.logo+"' width='50px' class='logo'/></td>\n" +
                        "                <td class=\"title\">"+e.length+"</td>\n" +
                        "                <td class=\"title\">"+e.depth+"</td>\n" +
                        "                <td class=\"title\">"+e.width+"</td>\n" +
                        "                <td class=\"title\">"+e.height+"</td>\n" +
                        "                <td class=\"title\">"+e.event_name+"</td>\n" +
                        "                <td>\n" +
                        "                    <div class=\"layui-btn-group\">\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Edit catalog' \n" +
                        "                                onclick=\"editForOpenPopups('✎ Edit catalog','"+e.id+"','720px','95%')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe642;</i>\n" +
                        "                        </button>\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Remove catalog' \n" +
                        "                                onclick=\"delPostRecord('"+e.id+"')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe640;</i>\n" +
                        "                        </button>\n" +
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