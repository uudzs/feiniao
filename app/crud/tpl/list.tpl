{extend name="common/base"/}
<!-- 主体 -->
{block name="body"}

<div class="p-3">
	<form class="layui-form gg-form-bar border-t border-x">
		<div class="layui-input-inline" style="width:300px;">
			<input type="text" name="keywords" placeholder="请输入关键字" class="layui-input" autocomplete="off" />
		</div>
		<div class="layui-input-inline">
		<button class="layui-btn layui-btn-normal" lay-submit="" lay-filter="searchform">提交搜索</button>
		</div>
	</form>
	<table class="layui-hide" id="<model>" lay-filter="<model>"></table>
</div>

<script type="text/html" id="toolbarDemo">
	<div class="layui-btn-container">
		<span class="layui-btn layui-btn-sm" lay-event="add" data-title="添加<name>">+ 添加<name></span>
	</div>
</script>

<script type="text/html" id="barDemo">
<div class="layui-btn-group"><a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="read">查看</a><a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a></div>
</script>

{/block}
<!-- /主体 -->

<!-- 脚本 -->
{block name="script"}
<script>
	const moduleInit = ['tool'];
	function feiniaoInit() {
		var table = layui.table,tool = layui.tool, form = layui.form;
		layui.pageTable = table.render({
			elem: '#<model>',
			title: '<name>列表',
			toolbar: '#toolbarDemo',
			url: '{:url("<apply><model>/datalist")}',
			page: true,
			limit: 20,
			cols: [
				[
				<listitems>
				{
					fixed: 'right',
					field: 'right',
					title: '操作',
					toolbar: '#barDemo',
					width: 136,
					align: 'center'
				}				
				]
			]
		});
		
		//监听表头工具栏事件
		table.on('toolbar(<model>)', function(obj){
			if (obj.event === 'add') {
				tool.side("{:url('<apply><model>/add')}");
				return false;
			}
		});

		//监听表格行工具事件
		table.on('tool(<model>)', function(obj) {
			var data = obj.data;
			if (obj.event === 'read') {
				tool.side('{:url("<apply><model>/read")}?<pk>='+obj.data.<pk>);
			}
			else if (obj.event === 'edit') {
				tool.side('{:url("<apply><model>/edit")}?<pk>='+obj.data.<pk>);
			}
			else if (obj.event === 'del') {
				layer.confirm('确定要删除该记录吗?', {
					icon: 3,
					title: '提示'
				}, function(index) {
					let callback = function (e) {
						layer.msg(e.msg);
						if (e.code == 0) {
							obj.del();
						}
					}
					tool.delete("{:url('<apply><model>/del')}", { <pk>: data.<pk> }, callback);
					layer.close(index);
				});
			}
			return false;
		});

		//监听搜索提交
		form.on('submit(searchform)', function(data) {
			layui.pageTable.reload({
				where: {
					keywords: data.field.keywords
				},
				page: {
					curr: 1
				}
			});
			return false;
		});
	}
</script>
{/block}
<!-- /脚本 -->