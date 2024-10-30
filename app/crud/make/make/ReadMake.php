<?php

namespace app\crud\make\make;

use app\crud\make\ToAutoMake;
use think\facade\App;
use think\facade\Db;
use think\console\Output;

class ReadMake implements ToAutoMake
{
    public function check($table, $path)
    {
        !defined('DS') && define('DS', DIRECTORY_SEPARATOR);

        $modelName = $table;
        $modelFilePath = base_path() . $path . DS . 'view' . DS . $modelName . DS . 'read.html';

        if (!is_dir(base_path() . $path . DS . 'view' . DS . $modelName)) {
            mkdir(base_path() . $path . DS . 'view'. DS . $modelName, 0755, true);
        }

        if (file_exists($modelFilePath)) {
            $output = new Output();
            $output->error("$modelName . DS . view.html已经存在");
            exit;
        }
    }

    public function make($table, $path, $other)
    {
        $readTpl = dirname(dirname(__DIR__)) . '/tpl/read.tpl';
        $tplContent = file_get_contents($readTpl);

        $model = $table;
        $filePath = empty($path) ? '' : DS . $path;
        $namespace = empty($path) ? '\\' : '\\' . $path . '\\';
		
		$prefix = config('database.connections.mysql.prefix');
        $column = Db::query('SHOW FULL COLUMNS FROM `' . $prefix . $table . '`');
        $pk = '';
        foreach ($column as $vo) {
            if ($vo['Key'] == 'PRI') {
                $pk = $vo['Field'];
                break;
            }
        }

		/*
		//读取数据结构生成字段
		$tritems ='';
		$detail ='$detail.';
		$index =0;
        foreach ($column as $key => $vo) {
			$field = $vo['Field'];
			$title = $vo['Comment']==''?$field:$vo['Comment'];
		if($field != 'id'){
		if(($index % 3) == 0){
			$tritems.="<tr>
			<td class='layui-td-gray-2'>{$title}</td>
			<td>{{$detail}{$field}}</td>"; 
		}else if(($index % 3) == 1){
				$tritems.="
			<td class='layui-td-gray-2'>{$title}</td>
			<td>{{$detail}{$field}}</td>";
		}else if(($index % 3) == 2){
				$tritems.="
			<td class='layui-td-gray-2'>{$title}</td>
			<td>{{$detail}{$field}}</td>
		</tr>
		";
		}
		$index++;
			}      
        }
		if(($index % 3) == 1){
			$tritems.="<td colspan='4'></td>
		</tr>";
		}
		if(($index % 3) == 2){
			$tritems.="<td colspan='2'></td>
		</tr>";
		}
		*/
		
		//读取提交的数据生成字段
        $field_column = get_cache('crud_r_'.$table);
		$tritems ='';
		$index =0;
		$inputHtml='';
		$textareaHtml='';
		$uploadHtml='';
		$summernoteHtml='';
        foreach ($field_column as $key => $vo) {
			if($vo['type'] == 'summernote'){
				$summernoteHtml.="<tr>".$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required'])."
		</tr>";
			}
			else if($vo['type'] == 'upload'){			
				$uploadHtml.="<tr>".$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required'])."
		</tr>";
			}else if($vo['type'] == 'textarea'){			
				$textareaHtml.="<tr>".$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required'])."
		</tr>";
			}
			else{
				if(($index % 3) == 0){
					$inputHtml.="<tr>".$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required']);
				}else if(($index % 3) == 1){
						$inputHtml.=$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required']);
				}else if(($index % 3) == 2){
						$inputHtml.=$this->make_form($vo['field'], $vo['type'], $vo['title'],$vo['required'])."
		</tr>";
				}
				$index++;
			}
		}
		
		if(($index % 3) == 1){
			$inputHtml.="<td colspan='4'></td>
		</tr>";
		}
		if(($index % 3) == 2){
			$inputHtml.="<td colspan='2'></td>
		</tr>";
		}
		
		$tritems=$inputHtml.$textareaHtml.$uploadHtml.$summernoteHtml;
        $tplContent = str_replace('<namespace>', $namespace, $tplContent);
        $tplContent = str_replace('<model>', $model, $tplContent);
        $tplContent = str_replace('<tritems>', $tritems, $tplContent);
        $tplContent = str_replace('<name>', $other, $tplContent);
		$tplContent = str_replace('<pk>', $pk, $tplContent);
		
        file_put_contents(base_path() . $path . DS . 'view' . DS . $model . DS . 'read.html', $tplContent);
    }
	
	public function make_form($field, $type, $title,$required)
    {
		$tem=[
			'input'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td>{$detail.'.$field.'}</td>',
			
			'datetime'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td>{$detail.'.$field.'|time_format=###,\'Y-m-d\'}</td>',
			
			'radio'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td>
				{eq name="$detail.'.$field.'" value="0"}选项一{/eq}
				{eq name="$detail.'.$field.'" value="1"}选项二{/eq}
			</td>',
			
			'checkbox'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td>
			{eq name="$detail.'.$field.'" value="1"}选项一{/eq}
			{eq name="$detail.'.$field.'" value="2"}选项二{/eq}
			</td>',
			
			'select'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td>
			{eq name="$detail.'.$field.'" value="1"}选项一{/eq}
			{eq name="$detail.'.$field.'" value="2"}选项二{/eq}
			</td>',
			
			
			'textarea'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td colspan="5">{$detail.'.$field.'}</td>',
			
			'upload'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td colspan="5">
				<img src="{$detail.'.$field.'}" onerror="javascript:this.src=\'{__ASSETS__}/init/images/nonepic600x360.jpg\';this.onerror=null;" style="width:200px; max-width:200px" />
			</td>',
			
			'summernote'=>'<td class="layui-td-gray-2">'.$title.'</td>
			<td colspan="5">
				{$detail.'.$field.'|raw}
			</td>'
		];
		
		return $tem[$type];
	}
}