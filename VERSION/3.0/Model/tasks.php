<?php

class tasks extends AmysqlModel
{
	// 取得任务
	function get_task($id = null, $crontab_md5 = null)
	{
		$where = '';
		$where .= (!empty($id)) ? " AND crontab_id = '$id' " : '';
		$where .= (!empty($crontab_md5)) ? " AND crontab_md5 = '$crontab_md5' " : '';
		$sql = "SELECT * FROM amh_crontab WHERE 1 $where ";
		Return $this -> _row($sql);
	}
	// 取得任务属性
	function get_task_value($tag)
	{
		foreach ($_POST as $key=>$val)
		{
			if(strpos($key, $tag) !== false)
			{
				if(strpos($key, 'time') !== false)
					Return $_POST[$tag . '_time'];
				elseif(strpos($key, 'period') !== false)
					Return $_POST[$tag . '_period_start'] . '-' . $_POST[$tag . '_period_end'];
				elseif(strpos($key, 'average') !== false)
					Return $_POST[$tag . '_average_start'] . '-' . $_POST[$tag . '_average_end'] . '/' . $_POST[$tag . '_average_input'];
				elseif(strpos($key, 'respectively') !== false)
					Return implode(',', $_POST[$tag . '_respectively']);
			}
		}
	}
	// 新增任务
	function insert_task()
	{
		$crontab_ssh = trim($_POST['crontab_ssh']);
		if(substr($crontab_ssh, 0, 3) != 'amh')
			Return false;

		$crontab_ssh = Functions::trim_cmd($crontab_ssh);
		$crontab_minute = $this -> get_task_value('minute');
		$crontab_hour = $this -> get_task_value('hour');
		$crontab_day = $this -> get_task_value('day');
		$crontab_month = $this -> get_task_value('month');
		$crontab_week = $this -> get_task_value('week');
		$crontab_type = 'web';

		$crontab_tmp = '/home/wwwroot/index/tmp/crontab.tmp';
		$cmd = "amh crontab -l > $crontab_tmp";
		$result = shell_exec($cmd);
		$crontab_content = file_get_contents($crontab_tmp) . $crontab_minute . ' ' . $crontab_hour . ' ' . $crontab_day . ' ' . $crontab_month . ' ' . $crontab_week . ' ' . $crontab_ssh . "\n";
		file_put_contents($crontab_tmp, $crontab_content, LOCK_EX);
		$cmd = "amh crontab $crontab_tmp";
		$result = shell_exec($cmd);

		$crontab_md5 = md5($crontab_minute.$crontab_hour.$crontab_day.$crontab_month.$crontab_week.$crontab_ssh);
		Return $this -> _insert('amh_crontab', array('crontab_minute' => $crontab_minute, 'crontab_hour' => $crontab_hour, 'crontab_day' => $crontab_day, 'crontab_month' => $crontab_month, 'crontab_week' => $crontab_week, 'crontab_ssh' => $crontab_ssh, 'crontab_type' => $crontab_type, 'crontab_md5' => $crontab_md5));
	}
	// 删除任务
	function del_task($id = null, $crontab_md5 = null)
	{
		if (!empty($id))
		{
			$task = $this -> get_task($id);
			if (!isset($task['crontab_id'])) Return false;
		}
		if(!empty($crontab_md5)) $task['crontab_md5'] = $crontab_md5;

		$cmd = 'amh crontab -l';
		$result = shell_exec($cmd);
		$task_list = explode("\n", $result);
		$crontab_content = '';
		$crontab_tmp = '/home/wwwroot/index/tmp/crontab.tmp';

		foreach ($task_list as $key=>$val)
		{
			$val_arr = explode(' ', ereg_replace("[ ]{1,}", " ", trim($val)));
			if($val_arr[0] != '#' && $val_arr[0][0] != '#' && count($val_arr) > 5)
			{
				$crontab_ssh = '';
				foreach ($val_arr as $k=>$v)
				{
					if($k > 4) $crontab_ssh .= ' ' . $v;
				}
				$crontab_ssh = trim($crontab_ssh);
				$crontab_md5 = md5($val_arr[0].$val_arr[1].$val_arr[2].$val_arr[3].$val_arr[4].$crontab_ssh);

				if ($crontab_md5 != $task['crontab_md5'])
					$crontab_content .= $val . "\n";
			}
		}

		file_put_contents($crontab_tmp, $crontab_content, LOCK_EX);
		$cmd = "amh crontab $crontab_tmp";
		shell_exec($cmd);
		Return true;
		
	}
	// 保存任务
	function save_task($id)
	{
		$task = $this -> get_task($id);
		if (!isset($task['crontab_id']) || $task['crontab_type'] == 'ssh') Return false;

		$crontab_ssh = trim($_POST['crontab_ssh']);
		if(substr($crontab_ssh, 0, 3) != 'amh')
			Return false;

		$crontab_ssh = Functions::trim_cmd($crontab_ssh);
		$crontab_minute = $this -> get_task_value('minute');
		$crontab_hour = $this -> get_task_value('hour');
		$crontab_day = $this -> get_task_value('day');
		$crontab_month = $this -> get_task_value('month');
		$crontab_week = $this -> get_task_value('week');
		$crontab_type = 'web';
		$crontab_md5 = md5($crontab_minute.$crontab_hour.$crontab_day.$crontab_month.$crontab_week.$crontab_ssh);

		$this -> _update('amh_crontab', array('crontab_minute' => $crontab_minute, 'crontab_hour' => $crontab_hour, 'crontab_day' => $crontab_day, 'crontab_month' => $crontab_month, 'crontab_week' => $crontab_week, 'crontab_ssh' => $crontab_ssh, 'crontab_type' => $crontab_type, 'crontab_md5' => $crontab_md5), " WHERE crontab_id = '$id' ");
		$this -> del_task(null, $task['crontab_md5']);

		$crontab_tmp = '/home/wwwroot/index/tmp/crontab.tmp';
		$cmd = "amh crontab -l > $crontab_tmp";
		$result = shell_exec($cmd);
		$crontab_content = file_get_contents($crontab_tmp) . $crontab_minute . ' ' . $crontab_hour . ' ' . $crontab_day . ' ' . $crontab_month . ' ' . $crontab_week . ' ' . $crontab_ssh . "\n";
		file_put_contents($crontab_tmp, $crontab_content, LOCK_EX);
		$cmd = "amh crontab $crontab_tmp";
		$result = shell_exec($cmd);
		Return true;
	}
	// 取得任务列表
	function get_task_list()
	{
		$cmd = 'amh crontab -l';
		$result = shell_exec($cmd);
		$task_list = explode("\n", $result);

		foreach ($task_list as $key=>$val)
		{
			$val_arr = explode(' ', ereg_replace("[ ]{1,}", " ", trim($val)));
			if($val_arr[0] != '#' && $val_arr[0][0] != '#' && count($val_arr) > 5)
			{
				$crontab_minute = $val_arr[0];
				$crontab_hour = $val_arr[1];
				$crontab_day = $val_arr[2];
				$crontab_month = $val_arr[3];
				$crontab_week = $val_arr[4];
				$crontab_ssh = '';
				$crontab_type = 'ssh';
				foreach ($val_arr as $k=>$v)
				{
					if($k > 4) $crontab_ssh .= ' ' . $v;
				}
				$crontab_ssh = trim($crontab_ssh);

				$crontab_md5 = md5($crontab_minute.$crontab_hour.$crontab_day.$crontab_month.$crontab_week.$crontab_ssh);
				$all_task_list[] = $crontab_md5;
				
				$task_info = $this -> get_task(null, $crontab_md5);
				if(!isset($task_info['crontab_id']))
				{
					$this -> _insert('amh_crontab', array('crontab_minute' => $crontab_minute, 'crontab_hour' => $crontab_hour, 'crontab_day' => $crontab_day, 'crontab_month' => $crontab_month, 'crontab_week' => $crontab_week, 'crontab_ssh' => $crontab_ssh, 'crontab_type' => $crontab_type, 'crontab_md5' => $crontab_md5));
				}
			}
		}

		if(count($all_task_list) > 0)
		{
			$sql = "DELETE FROM amh_crontab WHERE crontab_md5 NOT IN ('" . implode("','", $all_task_list) . "')";
			$this -> _query($sql);
		}
		else
		{
		    $sql = "TRUNCATE TABLE `amh_crontab`";
			$this -> _query($sql);
		}

		$sql = "SELECT * FROM amh_crontab ORDER BY crontab_id ASC ";
		Return $this -> _all($sql);
	}

}

?>