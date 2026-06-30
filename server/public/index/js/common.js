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