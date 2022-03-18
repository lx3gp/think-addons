<?php
// 这是系统自动生成的公共文件

	// //备份方法
	// public function backupAll ()
	// {
	// 	if( $this->tables )
	// 	{
	// 		$data = $this->genTitle();
	// 		foreach ( $this ->tables as $table )
	// 		{
	// 			//拿相关 create table 数据
	// 			$ctable = $this->get_create_table($table);
	// 			//生成表结构
	// 			$data .= $this->get_table_structure($ctable);
	// 			//表记录
	// 			$data .= $this->get_table_records($table);
	// 		}
	// 		$filename = $this->path . time() .'.sql';
	// 		return file_put_contents($filename, $data);
	// 	}
	// }

	// //还原方法  拆分sql语句,  因为之前保存到文件中的语句都是以 ;\r\n 结尾的, 所以... 
	// public function restore ($file)
	// {
	// 	$filename = $file;
	// 	if( !file_exists($filename) )
	// 	{
	// 		return false;
	// 	}
	// 	$str = fread( $hd = fopen($filename, "rb") , filesize($filename) );
	// 	$sqls = explode(";\r\n", $str);//所以... 这里拆分sql
	// 	if($sqls)
	// 	{
	// 		foreach($sqls as $sql)
	// 		{
	// 			$this->model ->query($sql);//逐条执行
	// 		}
	// 	}
	// 	fclose($hd);
	// 	return true;
	// }
	
	// //备份文件相关
	// public function getFileInfo()
	// {
	// 	$temp = array();
	// 	if( is_dir($this->path) )
	// 	{
	// 		$handler = opendir($this->path);
	// 		$num = 0;
	// 		while ( $file = readdir($handler) ){
	// 			if( $file !== '.' && $file !== '..' )
	// 			{
	// 				$filename = $this->path.$file;
	// 				$temp[$num]['name'] = $file;
	// 				$temp[$num]['size'] = ceil(filesize($filename)/1024);
	// 				$temp[$num]['time'] = date("Y-m-d H:i:s" ,filemtime($filename));
	// 				$temp[$num]['path'] = $filename;
	// 				$num ++;
	// 			}
	// 		}
	// 	}
	// 	return $temp;
	// }

	// //删除文件
	// public function delFile ($file)
	// {
	// 	if( file_exists($file) )
	// 	{
	// 		return unlink($file);
	// 	}
	// 	return false;
	// }
	
	// //sql 文件开头部分  可以省略 但 SET FOREIGN_KEY_CHECKS=0 最好有
	// private function genTitle()
	// {
	// 	$time = date("Y-m-d H:i:s" ,time());
	// 	$str = "/*************************\r\n";
 	// 	$str.= " * {$time} \r\n";
	// 	$str.= " ************************/\r\n";
	// 	$str.= "SET FOREIGN_KEY_CHECKS=0;\r\n";
	// 	return $str;
	// }

	// private function get_tables ()
	// {
	// 	$sql = 'show tables';
	// 	if( $data = $this->model ->fetchAll($sql) ) {
	// 		foreach ( $data as $val ) {
	// 			$this->tables[] = $val['Tables_in_'.$this->dbname];
	// 		}
	// 	}
	// }

	// //返回一个数组, 0=>表名称 ,1=>表结构(Create Table) 
	// private function get_create_table ($table)
	// {
	// 	$sql = "show create table $table";
	// 	$arr = $this->model ->fetchOne($sql);
	// 	return array_values($arr);
	// }

	// //生成表结构
	// private function get_table_structure ($ctable)
	// {
	// 	$str  = "-- ----------------------------\r\n";
	// 	$str .= "-- Table structure for `{$ctable[0]}`\r\n";
	// 	$str .= "-- ----------------------------\r\n";
	// 	$str .= "DROP TABLE IF EXISTS `{$ctable[0]}`;\r\n".$ctable[1].";\r\n\r\n";
	// 	return $str;
	// }

	// //表记录的sql语句拼接  当还原的时候  就是逐条插入记录 到对应的表
	// private function get_table_records ($table)
	// {
	// 	$sql = "select * from {$table}";
	// 	if( $data = $this->model ->fetchAll($sql) ) {
	// 		$str = "-- ----------------------------\r\n";
	// 		$str.= "-- Records of $table \r\n";
	// 		$str.= "-- ----------------------------\r\n";

	// 		foreach ( $data as $val ) {
	// 			if( $val ) {
	// 				//$keyArr = array();
	// 				$valArr = array();
	// 				//这里看情况了,
	// 				foreach ( $val as $k => $v ) {
	// 					//$keyArr[] = "`".$k."`";
	// 					//对单引号和换行符进行一下转义
	// 					$valArr[] = "'".str_replace( array("'","\r\n"), array("\'","\\r\\n"), $v )."'";
	// 				}
	// 				//$keys = implode(', ', $keyArr);
	// 				$values = implode(', ', $valArr);
	// 				$str .= "INSERT INTO `{$table}` VALUES ($values);\r\n";//省略了字段名称
	// 			}
	// 		}
	// 		$str .= "\r\n";
	// 		return $str;
	// 	}
	// 	return '';
	// }

	// private function check_path ()
	// {
	// 	if( !is_dir($this->path) ) {
	// 		mkdir($this->path ,0777 ,true);
	// 	}
	// }







// if(function_exists('movedirs')){
//     /**
//      * 移动文件/目录
//      * @param string $src
//      * @param string $dst
//      * @return string
//      */
//     function movedirs($src,$dst){
//         //	echo $src,$dst;
//         //验证文件名是否合法
//         $fileName = basename($dst);
//         if(!file_exists($src)){
//             return '原文件/目录不存在';
//         }
//         if(file_exists($dst)){
//             return '当前目录下存在同名文件/目录';
//         }
//         if(!rename($src, $dst)){
//             return '移动失败';
//         }
//         return true;
//     }
// }

// if (!function_exists('is_really_writable')) {
//     /**
//      * 判断文件或文件夹是否可写
//      * @param string $file 文件或目录
//      * @return    bool
//      */
//     function is_really_writable($file)
//     {
//         if (DIRECTORY_SEPARATOR === '/') {
//             return is_writable($file);
//         }
//         if (is_dir($file)) {
//             $file = rtrim($file, '/') . '/' . md5(mt_rand());
//             if (($fp = @fopen($file, 'ab')) === false) {
//                 return false;
//             }
//             fclose($fp);
//             @chmod($file, 0777);
//             @unlink($file);
//             return true;
//         } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
//             return false;
//         }
//         fclose($fp);
//         return true;
//     }
// }

// if (!function_exists('rmdirs')) {
//     /**
//      * 删除文件夹
//      * @param string $dirname  目录
//      * @param bool   $withself 是否删除自身
//      * @return boolean
//      */
//     function rmdirs($dirname, $withself = true)
//     {
//         if (!is_dir($dirname)) {
//             return false;
//         }
//         $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
//         foreach ($files as $fileinfo) {
//             $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
//             $todo($fileinfo->getRealPath());
//         }
//         if ($withself) {
//             @rmdir($dirname);
//         }
//         return true;
//     }
// }
