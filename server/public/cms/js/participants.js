/**
 * ajax 获取并加载每页的数据
 * @param toUrl
 * @param postData
 * @constructor
 */
function ToAjaxOpForPageDatas(toUrl,postData) {
    $.post(
        toUrl,
        postData,
        function (result) {
            if(result.status == 200){
                var str_html = '';
                $.each(result.data,function (i,e) {
                    str_html +=
                        "<tr class=\"tr-normal-"+e.unique_id+"\">\n" +
                        "                <td>"+(i+1)+"</td>\n" +
                        "                <td>"+e.unique_id+"</td>\n" +
                        "                <td class=\"title\">"+e.first_name+"</td>\n" +
                        "                <td class=\"title\">"+e.last_name+"</td>\n" +
                        "                <td class=\"title\">"+e.organisation+"</td>\n" +
                        "                <td class=\"title\">"+e.email+"</td>\n" +
                        "                <td>"+e.mobile +"</td>\n" +
                        "                <td>"+e.brochure_download_num +"</td>\n" +
                        "                <td>"+e.remarks +"</td>\n" +
                        "                <td>\n" +
                        "                    <div class=\"layui-btn-group\">\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Edit participant' \n" +
                        "                                onclick=\"editForOpenPopups('✎ Edit participant','"+e.unique_id+"','720px','420px')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe642;</i>\n" +
                        "                        </button>\n" +
                        "                               <button class=\"layui-btn layui-btn-sm\" title=\"View brochures\"\n" +
                        "                               onclick=\"viewForOpenPopups('✎ View brochures','{$vo.id}', '720px', '460px')\">\n" +
                        "                                   <i class=\"layui-icon\">&#xe601;</i>\n" +
                        "                               </button>\n" +
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