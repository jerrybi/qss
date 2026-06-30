/**
 * ajax 获取并加载每页的数据
 * @param toUrl
 * @param postData
 * @constructor
 */
function ToAjaxOpForPageAdmins(toUrl,postData) {
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
                        "                <td class='title'>"+e.user_name+"</td>\n" +
                        "                <td class=\"td-item\"><img class='layui-circle' src='"+e.picture+"'></td>\n" +

                        "                <td>"+e.role_tip+"</td>\n"+
                        "                <td>"+e.created_at +"</td>\n" +
                        "                <td>" +e.status_tip +"</td>\n" +
                        "                <td>\n" +
                        "                    <div class=\"layui-btn-group\">\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Edit' \n" +
                        "                                onclick=\"editForOpenPopups('✎ Admin edit','"+e.id+"', '48%', '65%')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe642;</i>\n" +
                        "                        </button>\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title='Delete' \n" +
                        "                                onclick=\"delPostRecord('"+e.id+"')\">\n" +
                        "                            <i class=\"layui-icon\">&#xe640;</i>\n" +
                        "                        </button>\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title=\"Update Password\"\n" +
                    "                        onclick=\"updatePasswordForOpenPopups('✎ Update Password','{$vo.id}', '48%', '65%')\">\n" +
                    "                        <i class=\"layui-icon layui-icon-password\"></i>\n" +
                    "                        </button>\n" +
                        "                    </div>\n" +
                        "                </td>\n" +
                        "            </tr>";
                });
                $(".table-tbody-normal").html(str_html);
            }else{
                //失败
                layer.msg(result.message);
            }
        },"JSON");
}