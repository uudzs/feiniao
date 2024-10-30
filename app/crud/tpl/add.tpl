{extend name="common/base"/}
<!-- 主体 -->
{block name="body"}
<form class="layui-form p-4">
	<h3 class="pb-3">新建<name></h3>
	<table class="layui-table layui-table-form">
		<tritems>
	</table>
	<div class="pt-3">
		<input type="hidden" name="<pk>" value="0"/>
		<button class="layui-btn layui-btn-normal" lay-submit="" lay-filter="webform">立即提交</button>
		<button type="reset" class="layui-btn layui-btn-primary">重置</button>
	</div>
</form>
{/block}
<!-- /主体 -->

<!-- 脚本 -->
{block name="script"}
<script>
	<moduleInit>

	function feiniaoInit() {
		var form = layui.form, tool = layui.tool;
		<datetimeScript>
		<uploadScript>
		<summernoteScript>		
		//监听提交
		form.on('submit(webform)', function (data) {
			<summernoteForm>
			let callback = function (e) {
				layer.msg(e.msg);
				if (e.code == 0) {
					tool.sideClose(1000);
				}
			}
			tool.post("{:url('<apply><model>/add')}", data.field, callback);
			return false;
		});
	}
</script>
{/block}
<!-- /脚本 -->