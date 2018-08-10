<?php
namespace app\index\controller;
use think\Db;
use think\Request;

class Export{
    public function test(){echo "12345";}
    public function switchDb(Request $request){
        $dbType=$request->post("db_type");
        $dbUser=$request->post("db_user");
        $dbPwd=$request->post("db_pwd");
        $dbHost=$request->post("db_host");
        $dbPort=$request->post("db_port");
        $dbName=$request->post("db_name");
        $config=$dbType."://".$dbUser.":".$dbPwd."@".$dbHost.":".$dbPort."/".$dbName."#utf8";
        if($dbType != "oracle"){
          $db=Db::connect($config,true);
        }
        switch ($dbType){
            case "mysql":
                $this->getTablesForMySQL($dbName,$db);
            break;
            case "pgsql":
                $this->getTablesForPg($dbName,$db);
                break;
            case "oracle":
            $dsn = new \PDO("oci:dbname=".$dbHost.":".$dbPort."/".$dbName."", $dbUser,$dbPwd);
                $this->getTablesOfOracle($dbName,$dsn);
                break;
        }
    }
//	public function DbConnect(){
//        return Db::connect('mysql://root:1234@127.0.0.1:3306/thinkphp#utf8');
//    }
	public function getTablesForMySQL($database='',$db){
		$result=$db->query("select t.TABLE_NAME,t.TABLE_COMMENT from information_schema.`TABLES` t where TABLE_SCHEMA='".$database."' AND t.TABLE_TYPE !='VIEW'");
		// $result=$data->select();
// print_r($result);
		$arr=array();
		$str="";$str='<style>
                @font-face {
                font-family:"Times New Roman";
                }
                @font-face {
                font-family:"&#23435;&#20307;";
                }
                @font-face {
                font-family:"Arial";
                }
                table{border-collapse:collapse;border-color:#000;}
                td{ border-color:#000; padding:10px 5px; vertical-align:middle;}
                h1{ text-align:left}
                h2{ text-align:left;}
                h3{ text-align:left;}
                </style>';
                $i=0;
		foreach ($result as $v) {
      $i++;
			// $arr=$this->exportWord($v["name"]);
      $arr=$db->query("select column_name,column_type,column_comment from information_schema.columns  where  TABLE_SCHEMA='".$database."' and table_name='".$v["TABLE_NAME"]."'");
			$str.="<h3>".$i.".表名：".$v["TABLE_COMMENT"]."(".$v["TABLE_NAME"].")</h3>";
			$str.="<table>";
			$str.=" <thead>
      <tr>
       <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
       background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
       <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
       style='font-family:宋体;color:windowtext'>字段</span></b></p>
       </td>
       <td valign=top style='width:84.85pt;border:solid windowtext 1.0pt;
       border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
       <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
       style='font-family:宋体;color:windowtext'>数据类型</span></b></p>
       </td>
       <td valign=top style='width:145.4pt;border:solid windowtext 1.0pt;
       border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
       <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
       style='font-family:宋体;color:windowtext'>说明</span></b></p>
       </td>
       <td valign=top style='width:70.6pt;border:solid windowtext 1.0pt;
       border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
       <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
       style='font-family:宋体;color:windowtext'>备注</span></b></p>
       </td>
      </tr>
     </thead>";
			foreach ($arr as $vc) {
				 $str.="<tr>
          <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
          border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>".$vc["column_name"]."</td>
          <td valign=top style='width:84.85pt;border-top:none;border-left:
          none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
          padding:0cm 5.4pt 0cm 5.4pt'>".$vc["column_type"]."</td>
          <td valign=top style='width:145.4pt;border-top:none;border-left:
          none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
          padding:0cm 5.4pt 0cm 5.4pt'>".$vc["column_comment"]."</td>
          <td valign=top style='width:70.6pt;border-top:none;border-left:none;
          border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
          padding:0cm 5.4pt 0cm 5.4pt'> </td>
         </tr>";
				// $str.="<tr><td>".$vc["column_name"]."</td><td>".$vc["column_type"]."</td><td>".$vc["column_comment"]."</td><td></td></tr>";
			}
			$str.="</table>";
			// print_r($arr);
			// echo $str;
		}
    $filename="exportfile/".$database."-".date("Y-m-d").".doc";
    // echo $filename;
    file_put_contents($filename,$str);

		echo "<a href='../../../".$filename."'>点击下载<a/>";

	}

    public function getTableColumnOfPg($tableName='',$db){
        $sql='
          SELECT a.attname AS "column_name",
            pg_catalog.format_type(a.atttypid, a.atttypmod) as "type",
            CASE WHEN a.attnotnull IS TRUE
              THEN \'NO\'
              ELSE \'YES\'
            END AS "null",
            CASE WHEN pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, true) IS NOT NULL
              THEN pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, true)
            END as "Default",
            CASE WHEN pg_catalog.col_description(a.attrelid, a.attnum) IS NULL
            THEN \'\'
            ELSE pg_catalog.col_description(a.attrelid, a.attnum)
            END  AS "comments"
          FROM pg_catalog.pg_attribute a
          LEFT JOIN pg_catalog.pg_attrdef adef ON a.attrelid=adef.adrelid AND a.attnum=adef.adnum
          LEFT JOIN pg_catalog.pg_type t ON a.atttypid=t.oid
          WHERE a.attrelid =
            (SELECT oid FROM pg_catalog.pg_class WHERE relname=' . "'$tableName'" . '
              AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE
              nspname = \'public\')
            )
          AND a.attnum > 0 AND NOT a.attisdropped
          ORDER BY a.attnum';
        $result=$db->query($sql);
     return $result;
  }


    public function getTablesForPg($database='',$db){
        $sql="SELECT tablename,obj_description(relfilenode,'pg_class') tablecomment FROM pg_tables a,pg_class b WHERE a.tablename = b.relname and a.tablename NOT LIKE 'pg%' AND a.tablename NOT LIKE 'sql_%' ORDER BY a.tablename";
        $result=$db->query($sql);
        // print_r($result);
        $arr=array();
        $str="";
        $str='<style>
            @font-face {
            font-family:"Times New Roman";
            }
            @font-face {
            font-family:"&#23435;&#20307;";
            }
            @font-face {
            font-family:"Arial";
            }
            table{border-collapse:collapse;border-color:#000;}
            td{ border-color:#000; padding:10px 5px; vertical-align:middle;}
            h1{ text-align:left}
            h2{ text-align:left;}
            h3{ text-align:left;}
            </style>';
        $i=0;
        foreach ($result as $v) {
          $i++;
          $arr=$this->getTableColumnOfPg($v["tablename"],$db);
          // print_r($arr);
          // $arr=$data->query("select column_name,column_type,column_comment from information_schema.columns  where table_name='".$v["table_name"]."'");
          $str.="<h3>".$i.".表名：".$v["tablecomment"]."(".$v["tablename"].")</h3>";
          $str.="<table>";
          $str.=" <thead>
          <tr>
           <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
           background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>字段</span></b></p>
           </td>
           <td valign=top style='width:84.85pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>数据类型</span></b></p>
           </td>
           <td valign=top style='width:145.4pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>说明</span></b></p>
           </td>
           <td valign=top style='width:70.6pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>备注</span></b></p>
           </td>
          </tr>
         </thead>";
          foreach ($arr as $vc) {
             $str.="<tr>
              <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
              border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>".$vc["column_name"]."</td>
              <td valign=top style='width:84.85pt;border-top:none;border-left:
              none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'>".$vc["type"]."</td>
              <td valign=top style='width:145.4pt;border-top:none;border-left:
              none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'>".$vc["comments"]."</td>
              <td valign=top style='width:70.6pt;border-top:none;border-left:none;
              border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'> </td>
             </tr>";
            // $str.="<tr><td>".$vc["column_name"]."</td><td>".$vc["column_type"]."</td><td>".$vc["column_comment"]."</td><td></td></tr>";
          }
          $str.="</table>";
          // print_r($arr);
          // echo $str;
        }
        $filename="exportfile/".$database."-".date("Y-m-d").".doc";
        // echo $filename;
        file_put_contents($filename,$str);

        echo "<a href='../../../".$filename."'>点击下载<a/>";

  }

    public function getTablesOfOracle($database='',$db){
        // $result=$data->query("select tablename from pg_tables where schemaname='public'");
        // $result_tab=$data->query("select TABLE_NAME from information_schema.`TABLES`  where TABLE_SCHEMA='hanguguan' AND TABLE_NAME !='add_col'");
        $stmt=$db->query("select t.TABLE_NAME,t.COMMENTS from user_tab_comments t where t.table_type = 'TABLE' ");
        $result_tab = $stmt->fetchAll(\PDO::FETCH_ASSOC );
        // print_r($result);
        $arr=array();
        $str="<meta charset=\"gbk\">";
        $str.='<style>
            @font-face {
            font-family:"Times New Roman";
            }
            @font-face {
            font-family:"&#23435;&#20307;";
            }
            @font-face {
            font-family:"Arial";
            }
            table{border-collapse:collapse;border-color:#000;}
            td{ border-color:#000; padding:10px 5px; vertical-align:middle;}
            h1{ text-align:left}
            h2{ text-align:left;}
            h3{ text-align:left;}
            </style>';
            // print_r($result_tab);
        foreach ($result_tab as $v) {
          // $arr=$this->getTableColumnOfPg($v["tablename"]);
          // $arr=$data->query("select column_name,column_type,column_comment from information_schema.columns  where table_name='".$v["TABLE_NAME"].$v["TABLE_NAME"]."'");
          $stmt1=$db->query(" select t.COLUMN_NAME,t.COMMENTS as column_comment,c.DATA_TYPE as column_type,decode(c.DATA_TYPE,'NUMBER',c.DATA_PRECISION,c.CHAR_LENGTH) as jingdu,c.DATA_SCALE from user_col_comments t , user_tab_columns c where t.TABLE_NAME=c.TABLE_NAME and t.COLUMN_NAME=c.COLUMN_NAME and t.TABLE_NAME= '".$v["TABLE_NAME"]."'");
          $arr = $stmt1->fetchAll(\PDO::FETCH_ASSOC );
          $str.="<h3>".iconv("UTF-8","GBK",'表名：')."".$v["COMMENTS"]."(".$v["TABLE_NAME"].")</h3>";
          $str.="<table>";
          $str.=" <thead>
          <tr>
          <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
           background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'序号')."</span></b></p>
           </td>
           <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
           background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'名称')."</span></b></p>
           </td>
           <td valign=top style='width:84.85pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'类型')."</span></b></p>
           </td>
           <td valign=top style='width:145.4pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'别名')."</span></b></p>
           </td>
           <td valign=top style='width:70.6pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'长度')."</span></b></p>
           </td>
           <td valign=top style='width:70.6pt;border:solid windowtext 1.0pt;
           border-left:none;background:#80C687;padding:0cm 5.4pt 0cm 5.4pt'>
           <p class=MsoNormal align=center style='text-align:center;text-indent:21.1pt'><b><span
           style='font-family:宋体;color:windowtext'>".iconv("UTF-8","GBK",'精度')."</span></b></p>
           </td>
          </tr>
         </thead>";
//            $str = ;
          $i=0;
          foreach ($arr as $vc) {
            $i++;
             $str.="<tr>
             <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
              border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>".$i."</td>
              <td valign=top style='width:119.85pt;border:solid windowtext 1.0pt;
              border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>".$vc["COLUMN_NAME"]."</td>
              <td valign=top style='width:84.85pt;border-top:none;border-left:
              none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'>".$vc["COLUMN_TYPE"]."</td>
              <td valign=top style='width:145.4pt;border-top:none;border-left:
              none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'>".$vc["COLUMN_COMMENT"]."</td>
              <td valign=top style='width:70.6pt;border-top:none;border-left:none;
              border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'> ".$vc["JINGDU"]."</td>
              <td valign=top style='width:70.6pt;border-top:none;border-left:none;
              border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
              padding:0cm 5.4pt 0cm 5.4pt'> ".$vc["DATA_SCALE"]."</td>
             </tr>";
            // $str.="<tr><td>".$vc["column_name"]."</td><td>".$vc["column_type"]."</td><td>".$vc["column_comment"]."</td><td></td></tr>";
          }
          $str.="</table>";
          // print_r($arr);
          // echo $str;
        }
        $filename="exportfile/".$database."-".date("Y-m-d").".doc";
        // echo $filename;
        file_put_contents($filename,$str);

        echo "<a href='../../../".$filename."'>点击下载<a/>";

  }

}
?>