layui.define(['layer'],function(exports){
	//提示：模块也可以依赖其它模块，如：layui.define('layer', callback);
	let layer = layui.layer,element = layui.element, form = layui.form, upload = layui.upload,uploadindex=0;
	let isObject = function(obj) {
		return Object.prototype.toString.call(obj) === '[object Object]';
	}
	const opts={
		"title":'上传图片',
		"url": uploadplus_upload_path ? uploadplus_upload_path : '/admin/api/upload/thumb/500',
		"target":'gogoupload',
		"type":1,
		"max":31,
		"callback": null
	};
	var uploadplus = function(options){
		this.settings = $.extend({}, opts, options);
		this.settings.index = uploadindex;
		uploadindex++;
		this.createStyle();
		var me=this;
		if(isObject(me.settings.target)){
			me.init();
		}
		else{
			$('#'+me.settings.target).click(function(){
				me.init();
			});
		}
	};    
	uploadplus.prototype = {		
		init: function () {
			var me = this;
			var area =[[],['640px','360px'],['928px','610px']];
			this.layerindex = layer.open({
				'title':me.settings.title,
				'area':area[me.settings.type],
				'content':me.render(),
				'type':1,
				'success':function(){
					if(me.settings.type==1){
						me.uploadOne();	
					}else{
						me.uploadMore();	
					}							
				}
			});
		},
		render: function (){
			var me = this;
			var template_one = '<div class="layui-form p-3">\
						<div class="layui-form-item">\
							<label class="layui-form-label">来源：</label>\
							<div class="layui-input-block">\
								<input type="radio" name="uploadtype" lay-filter="type" value="1" title="本地上传" checked>\
								<input type="radio" name="uploadtype" lay-filter="type" value="2" title="网络图片">\
							</div>\
						</div>\
						<div id="uploadType1">\
							<div class="layui-form-item">\
								<label class="layui-form-label">文件：</label>\
								<div class="layui-input-block">\
									<span class="gg-upload-files">.jpg、.jpeg、.gif、.png、.bmp</span><button type="button" class="layui-btn layui-btn-normal" id="ggUploadBtn'+me.settings.index+'">选择文件</button>\
								</div>\
							</div>\
							<div class="layui-form-item">\
								<label class="layui-form-label"></label>\
								<div class="layui-input-block">\
									<span class="gg-upload-tips">只能上传 .jpg、.jpeg、.gif、.png、.bmp 文件</span>\
								</div>\
							</div>\
							<div class="layui-form-item">\
								<label class="layui-form-label"></label>\
								<div class="layui-input-block" id="ggUploadChoosed'+me.settings.index+'"></div>\
							</div>\
							<div class="layui-progress upload-progress" lay-showpercent="yes" lay-filter="upload-progress-'+me.settings.index+'" style="margin-bottom:12px; margin-left:100px; width:320px; display:none;">\
							  <div class="layui-progress-bar layui-bg-blue" lay-percent=""><span class="layui-progress-text"></span></div>\
							</div>\
							<div class="layui-form-item layui-form-item-sm">\
								<label class="layui-form-label"></label>\
								<div class="layui-input-block">\
									<button type="button" class="layui-btn" id="uploadNow'+me.settings.index+'">开始上传</button>\
								</div>\
							</div>\
						</div>\
						<div id="uploadType2" style="display:none; width:480px;">\
							<div class="layui-form-item">\
								<label class="layui-form-label">URL地址：</label>\
								<div class="layui-input-block">\
									<input type="text" name="img_url" placeholder="" autocomplete="off" class="layui-input">\
								</div>\
							</div>\
							<div class="layui-form-item">\
								<label class="layui-form-label">图片名称：</label>\
								<div class="layui-input-block">\
									<input type="text" name="img_name" placeholder="" autocomplete="off" class="layui-input">\
								</div>\
							</div>\
							<div class="layui-form-item layui-form-item-sm">\
								<label class="layui-form-label"></label>\
								<div class="layui-input-block">\
									<span class="layui-btn" id="uploadAjax'+me.settings.index+'">确定保存</span>\
								</div>\
							</div>\
						</div>\
				</div>';
			var template_more = '<div class="layui-form p-3">\
							<div id="ggUploadBox'+me.settings.index+'" class="gg-upload-box select">\
								<div id="ggUploadBtn'+me.settings.index+'" class="gg-upload-btn"><div class="gg-upload-btn-box"><i class="layui-icon layui-icon-addition"></i><br/>点击上传图片</div></div>\
							</div>\
							<div class="layui-progress upload-progress" lay-showpercent="yes" lay-filter="progress-'+me.settings.index+'" style="margin:12px 0; width:900px;">\
								<div class="layui-progress-bar layui-bg-blue" lay-percent=""><span class="layui-progress-text"></span></div>\
							</div>\
							<div class="layui-form-item layui-form-item-sm">\
								<span class="gg-upload-tips">注：只能上传 jpg、.jpeg、.gif、.png、.bmp 文件，单次最多上传 '+me.settings.max+' 张图片，单张图片最大不要超过10M。</span>\
								<button type="button" class="layui-btn" id="uploadNow'+me.settings.index+'">开始上传</button>\
								<button type="button" class="layui-btn layui-btn-primary" id="uploadClear'+me.settings.index+'">清空列表</button>\
								<button type="button" class="layui-btn layui-btn-normal" id="uploadOk'+me.settings.index+'">提交</button>\
							</div>\
						</div>';
			return me.settings.type==1?template_one:template_more;
		},
		uploadOne:function(){
			var me = this;
			form.render();					
			form.on('radio(type)', function(data){
				if(data.value==1){
					$('#uploadType1').show();
					$('#uploadType2').hide();
				}
				else{
					$('#uploadType1').hide();
					$('#uploadType2').show();
				}
			}); 					
			//选文件
			var uploadOne = upload.render({
				elem: '#ggUploadBtn'+me.settings.index
				,url: me.settings.url
				,auto: false
				,accept: 'file' //普通文件
				,exts: 'png|jpg|gif|jpeg|bmp' //只允许上传文件格式
				,bindAction: '#uploadNow'+me.settings.index
				,choose: function(obj){
					obj.preview(function(index, file, result){
						$('#ggUploadChoosed'+me.settings.index).html('已选择：'+file.name);
					});
				}
				,before: function(obj){
					$('.upload-progress').show();
					element.progress('upload-progress-'+me.settings.index, '0%');
				}
				,progress: function(n, elem, e){
					console.log(n);
					element.progress('upload-progress-'+me.settings.index, n + '%');
				}
				,done: function(res){
					layer.msg(res.msg);
					if(res.code==0){
						me.settings.callback(res.data);
						layer.close(me.layerindex);
					}							
				}
			});
					
			$('#uploadAjax'+me.settings.index).on('click',function(){
				let url=$('[name="img_url"]').val();
				let name=$('[name="img_name"]').val();
				if(url == ''){
					layer.msg('请输入图片URL');
					return false;
				}
				if(name == ''){
					layer.msg('请输入图片名称');
					return false;
				}
				let res={
					filepath:url,
					name:name,
					id:0
				}
				me.settings.callback(res);
				layer.close(me.layerindex);
			})
		},
		uploadMore:function(){
			var me = this,file_lists=[];
			console.log(file_lists);
			var uploadList = upload.render({
				elem: '#ggUploadBtn'+me.settings.index
				,elemList: $('#ggUploadBox'+me.settings.index) //列表元素对象
				,url: me.settings.url
				,accept: 'file'
				,exts: 'png|jpg|gif|jpeg|bmp' //只允许上传文件格式
				,multiple: true
				,number: me.settings.max
				,auto: false
				,bindAction: '#uploadNow'+me.settings.index
				,choose: function(obj){
					var that = this;
					var files = this.files = obj.pushFile(); //将每次选择的文件追加到文件队列
					that.elemList.removeClass('select').addClass('selected');
					//读取本地文件
					obj.preview(function(index, file, result){
						var card = $('<div class="gg-upload-card" id="ggUploadCard'+index+'">\
												<div class="gg-upload-card-box">\
													<img alt="'+ file.name +'" class="gg-upload-card-img" src="'+ result +'">\
													<div class="gg-upload-card-bar"><div class="layui-progress" lay-filter="progress-card-'+ index +'"><div class="layui-progress-bar" lay-percent=""></div></div></div>\
													<div class="gg-upload-card-text">'+ file.name +'</div>\
													<div class="gg-upload-card-reload"><button type="button" class="layui-btn layui-btn-xs">重新上传</button></div>\
													<div class="gg-upload-card-del" data-index="'+index+'"><button type="button" class="layui-btn layui-btn-xs layui-btn-radius layui-btn-danger"><i class="layui-icon layui-icon-close"></i></button></div>\
												</div>\
											</div>');					
						//单个重传
						card.find('.gg-upload-card-reload').on('click', function(){
							obj.upload(index, file);
						});
					
						//删除
						card.find('.gg-upload-card-del').on('click', function(){
							delete files[index]; //删除对应的文件
							card.remove();
							uploadList.config.elem.next()[0].value = ''; //清空 input file 值，以免删除后出现同名文件不可选
						});
					
						that.elemList.append(card);
						element.render('progress'); //渲染新加的进度条组件
					});
				}
				,done: function(res, index, upload){ //成功的回调
					var that = this;
					if(res.code==0){
						delete this.files[index]; //删除文件队列已经上传成功的文件
						that.elemList.find('#ggUploadCard'+ index).addClass('uploadok');
						file_lists.push(res.data);
					}
					else{
						layer.msg(res.msg);
						this.error(index, upload);
					}
				}
				,allDone: function(obj){ //多文件上传完毕后的状态回调
					//console.log(obj);
					layer.msg('上传成功');
					me.settings.callback(file_lists,obj);
					layer.close(me.layerindex);
				}
				,error: function(index, upload){ //错误回调
				  var that = this;
				  var tr = that.elemList.find('#ggUploadCard'+ index).addClass('reload'); //显示重传
				}
				,progress: function(n, elem, e, index){
					element.progress('progress-card-'+ index, n + '%'); //执行进度条。n 即为返回的进度百分比
				}
			});
			
			$('#uploadClear'+me.settings.index).click(function(){
				$('#ggUploadBox'+me.settings.index).find('.gg-upload-card-del').click();						
			})
			$('#uploadOk'+me.settings.index).click(function(){
				if(me.settings.files.length>0){
					me.settings.callback(me.settings.files);
					layer.close(me.layerindex);
				}
				else{
					layer.msg('请先点击开始上传按钮上传');
				}
			})
		},	
		createStyle:function(){
			var cssText='.gg-upload-files{background-color: #ffffff; border:1px solid #e4e7ed;color: #c0c4cc;cursor: not-allowed; padding:0 12px; box-sizing: border-box; display: inline-block; font-size: inherit; height: 38px; line-height: 35px; margin-right:8px; border-radius:2px;}\
			.gg-upload-box{background-color:#f8f8f8; border:1px solid #eee; border-radius:6px; width:888px; height:440px; padding:5px; overflow-y:auto; margin:0 auto; position:relative;-webkit-user-select:none;-moz-user-select:none-ms-user-select:none;}\
			.select .gg-upload-btn{width:100%; height:100%; position:absolute;top:0;left:0; line-height:440px;}\
			.select .gg-upload-btn-box{width:100%; height:100%; box-sizing: border-box; padding-top:160px; line-height:1.2;text-align:center; cursor:pointer; color:#49bc85;font-size:22px;}\
			.select .gg-upload-btn-box i{font-size:60px;}\
			.selected .gg-upload-btn{width:100px; height:100px; float:left; padding:5px;}\
			.selected .gg-upload-btn-box{width:100px; height:100px; box-sizing: border-box; background-color:#eaf7f0; border:1px solid #49bc85; padding-top:16px; line-height:1.2;font-size:14px; text-align:center; cursor:pointer; color:#49bc85}\
			.selected .gg-upload-btn-box i{font-size:36px;}\
			.gg-upload-card{width:100px; height:100px; float:left; padding:5px;}\
			.gg-upload-card-box{width:100px; height:100px; box-sizing: border-box; background-color:#fff; border:1px solid #eee;position: relative;overflow: hidden;}\
			.gg-upload-card-box img {width: 100px; height: 100px; object-fit: cover;}\
			.gg-upload-card-text{background-color:rgba(0,0,0,.618); color:#fff; position:absolute;left:0; bottom:0; line-height:1.6; font-size:12px; width:100px; text-overflow:hidden; white-space: nowrap; text-overflow: ellipsis;}\
			.gg-upload-card-reload{width:50px; height:32px; position:absolute; top:5px; left:3px; font-size:12px;display:none;}\
			.gg-upload-card-del{width:32px; height:32px; position:absolute; top:5px; right:0; display:none;}\
			.gg-upload-card:hover .gg-upload-card-del{display:block;}\
			.uploadok.gg-upload-card .gg-upload-card-del{display:none;}\
			.reload.gg-upload-card .gg-upload-card-reload{display:block;}\
			.gg-upload-card-bar{width:100%; position:absolute;left:0; bottom:16px;}\
			.gg-upload-tips{color:#969696; font-size:12px; margin-right:20px;}';
			
			var document = window.document;
			var styleTag = document.createElement("style");
			styleTag.setAttribute("type", "text/css");
			if (styleTag.styleSheet) {    //ie
				styleTag.styleSheet.cssText += cssText;
			}
			else{			
				styleTag.innerHTML = cssText;
			}        
			document.getElementsByTagName("head").item(0).appendChild(styleTag);
		}
	}

  //输出接口
  exports('uploadplus', uploadplus);
});   