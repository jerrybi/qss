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
                        "<tr class=\"tr-normal-"+e.id+"\">\n" +
                        "                <td>"+e.id+"</td>\n" +
                        "                <td class=\"title\">"+e.login_name+"</td>\n" +
                        "                <td class=\"title\">"+e.company+"</td>\n" +
                        "                <td class=\"title\">"+e.event_name +"</td>\n" +
                        "                <td>\n" +
                        "                    <div class=\"layui-btn-group\">\n" +
                        "                        <button class=\"layui-btn layui-btn-sm\" title=\"Reset password\"\n" +
                        "                            onclick=\"resetPassword('"+e.id+"','"+e.email+"')\">\n" +
                        "                                   <i class=\"fa fa-stop-circle-o\"></i>\n" +
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

function resetPassword(id,email){
    layer.confirm('Are you sure to reset the password?the new password will be sent to the email '+email,
        {icon:3,title:'Confirm',btn:['Confirm','Cancel']},function(index){
            var url = $('.reset_url').val();
            url = url.replace('opid', id);
            $.post(url, {},
                function (result) {
                    layer.msg(result.message);
                },"JSON");
            layer.close(index);
        },function (index) {
            layer.close(index);
        });
}